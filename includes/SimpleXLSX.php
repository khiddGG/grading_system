<?php
/**
 * SimpleXLSX php class
 * MS Excel 2007+ workbooks reader
 * https://github.com/shuchkin/simplexlsx
 */

namespace Shuchkin;

use SimpleXMLElement;

class SimpleXLSX
{
    // Don't remove this string! Created by Sergey Shuchkin sergey.shuchkin@gmail.com
    public static $CF = [ // Cell formats
        0 => 'General',
        1 => '0',
        2 => '0.00',
        3 => '#,##0',
        4 => '#,##0.00',
        9 => '0%',
        10 => '0.00%',
        11 => '0.00E+00',
        12 => '# ?/?',
        13 => '# ??/??',
        14 => 'mm-dd-yy',
        15 => 'd-mmm-yy',
        16 => 'd-mmm',
        17 => 'mmm-yy',
        18 => 'h:mm AM/PM',
        19 => 'h:mm:ss AM/PM',
        20 => 'h:mm',
        21 => 'h:mm:ss',
        22 => 'm/d/yy h:mm',
        37 => '#,##0 ;(#,##0)',
        38 => '#,##0 ;[Red](#,##0)',
        39 => '#,##0.00;(#,##0.00)',
        40 => '#,##0.00;[Red](#,##0.00)',
        44 => '_("$"* #,##0.00_);_("$"* \(#,##0.00\);_("$"* "-"??_);_(@_)',
        45 => 'mm:ss',
        46 => '[h]:mm:ss',
        47 => 'mmss.0',
        48 => '##0.0E+0',
        49 => '@',
        27 => '[$-404]e/m/d',
        30 => 'm/d/yy',
        36 => '[$-404]e/m/d',
        50 => '[$-404]e/m/d',
        57 => '[$-404]e/m/d',
        59 => 't0',
        60 => 't0.00',
        61 => 't#,##0',
        62 => 't#,##0.00',
        67 => 't0%',
        68 => 't0.00%',
        69 => 't# ?/?',
        70 => 't# ??/??',
    ];
    public $nf = []; // number formats
    public $cellFormats = []; // cellXfs
    public $datetimeFormat = 'Y-m-d H:i:s';
    public $debug;
    public $activeSheet = 0;
    public $rowsExReader;
    public $sheets;
    public $sheetFiles = [];
    public $sheetMetaData = [];
    public $sheetRels = [];
    public $styles;
    public $package;
    public $sharedstrings;
    public $date1904 = 0;
    public $errno = 0;
    public $error = false;
    public $theme;

    public function __construct($filename = null, $is_data = null, $debug = null)
    {
        if ($debug !== null) {
            $this->debug = $debug;
        }
        $this->package = [
            'filename' => '',
            'mtime' => 0,
            'size' => 0,
            'comment' => '',
            'entries' => []
        ];
        if ($filename && $this->unzip($filename, $is_data)) {
            $this->parseEntries();
        }
    }

    public static function parse($filename, $is_data = false, $debug = false)
    {
        $xlsx = new self();
        $xlsx->debug = $debug;
        if ($xlsx->unzip($filename, $is_data)) {
            $xlsx->parseEntries();
        }
        if ($xlsx->success()) {
            return $xlsx;
        }
        return false;
    }

    public function success()
    {
        return !$this->error;
    }

    public function rows($worksheetIndex = 0)
    {
        if (($ws = $this->worksheet($worksheetIndex)) === false) {
            return false;
        }
        $rows = [];
        $curR = 0;
        foreach ($ws->sheetData->row as $row) {
            $curC = 0;
            foreach ($row->c as $c) {
                list($r, $v) = $this->getCell($c);
                $rows[$curR][$curC] = $v;
                $curC++;
            }
            $curR++;
        }
        return $rows;
    }

    public function worksheet($worksheetIndex = 0)
    {
        if (isset($this->sheets[$worksheetIndex])) {
            return $this->sheets[$worksheetIndex];
        }
        return false;
    }

    public function unzip($filename, $is_data = false)
    {
        if ($is_data) {
            $vZ = $filename;
        } else {
            if (!is_readable($filename)) return false;
            $vZ = file_get_contents($filename);
        }
        $aE = explode("\x50\x4b\x03\x04", $vZ);
        array_shift($aE);
        foreach ($aE as $vZ) {
            $aP = unpack('v1VN/v1GPF/v1CM/v1FT/v1FD/V1CRC/V1CS/V1UCS/v1FNL/v1EFL', $vZ);
            $nF = $aP['FNL'];
            $mF = $aP['EFL'];
            $name = substr($vZ, 26, $nF);
            if (substr($name, -1) === '/') continue;
            $data = substr($vZ, 26 + $nF + $mF, $aP['CS']);
            $this->package['entries'][] = [
                'data' => $data,
                'ucs' => (int)$aP['UCS'],
                'cm' => $aP['CM'],
                'name' => basename($name),
                'path' => dirname($name) === '.' ? '' : dirname($name)
            ];
        }
        return true;
    }

    public function getEntryData($name)
    {
        $name = str_replace('\\', '/', $name);
        $dir = dirname($name) === '.' ? '' : dirname($name);
        $base = basename($name);
        foreach ($this->package['entries'] as &$entry) {
            if (strcasecmp($entry['path'], $dir) === 0 && strcasecmp($entry['name'], $base) === 0) {
                if ($entry['cm'] == 8) $entry['data'] = gzinflate($entry['data']);
                return $entry['data'];
            }
        }
        return false;
    }

    public function parseEntries()
    {
        $this->sharedstrings = [];
        $this->sheets = [];
        $workbookXML = $this->getEntryData('xl/workbook.xml');
        if (!$workbookXML) return false;
        $workbook = new SimpleXMLElement($workbookXML);
        foreach ($workbook->sheets->sheet as $s) {
            $rId = (string)$s['id'];
            $sheetFile = "xl/worksheets/sheet" . substr($rId, 3) . ".xml";
            $sheetXML = $this->getEntryData($sheetFile);
            if ($sheetXML) $this->sheets[] = new SimpleXMLElement($sheetXML);
        }
        $ssXML = $this->getEntryData('xl/sharedStrings.xml');
        if ($ssXML) {
            $ss = new SimpleXMLElement($ssXML);
            foreach ($ss->si as $si) $this->sharedstrings[] = (string)$si->t;
        }
        return true;
    }

    public function getCell($c)
    {
        $t = (string)$c['t'];
        $v = (string)$c->v;
        if ($t === 's') $v = $this->sharedstrings[(int)$v];
        return [$c['r'], $v];
    }
}
