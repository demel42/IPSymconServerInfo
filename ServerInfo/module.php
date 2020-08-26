<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen
require_once __DIR__ . '/../libs/local.php';   // lokale Funktionen

class ServerInfo extends IPSModule
{
    use ServerInfoCommonLib;
    use ServerInfoLocalLib;

    public static $NUM_DEVICE = 4;

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyBoolean('module_disable', false);

        $this->RegisterPropertyString('partition0_device', '');
        $this->RegisterPropertyString('partition1_device', '');
        $this->RegisterPropertyString('partition2_device', '');
        $this->RegisterPropertyString('partition3_device', '');
        $this->RegisterPropertyString('disk0_device', '');
        $this->RegisterPropertyString('disk1_device', '');
        $this->RegisterPropertyString('disk2_device', '');
        $this->RegisterPropertyString('disk3_device', '');

        $this->RegisterPropertyInteger('update_interval', '0');

        $this->RegisterTimer('UpdateData', 0, 'ServerInfo_UpdateData(' . $this->InstanceID . ');');

        $this->CreateVarProfile('ServerInfo.Frequency', VARIABLETYPE_INTEGER, ' MHz', 0, 0, 0, 0, '');
        $this->CreateVarProfile('ServerInfo.MB', VARIABLETYPE_FLOAT, ' MB', 0, 0, 0, 0, '');
        $this->CreateVarProfile('ServerInfo.GB', VARIABLETYPE_FLOAT, ' GB', 0, 0, 0, 0, '');
        $this->CreateVarProfile('ServerInfo.Usage', VARIABLETYPE_FLOAT, ' %', 0, 0, 0, 1, '');
        $this->CreateVarProfile('ServerInfo.Duration', VARIABLETYPE_INTEGER, ' sec', 0, 0, 0, 0, '');
        $this->CreateVarProfile('ServerInfo.Temperature', VARIABLETYPE_FLOAT, ' °C', 0, 0, 0, 0, '');
        $this->CreateVarProfile('ServerInfo.Load', VARIABLETYPE_FLOAT, '', 0, 0, 0, 2, '');
    }

    private function CheckPrerequisites()
    {
        $s = '';
        $r = [];

        $sys = IPS_GetKernelPlatform();
        if (!in_array($sys, ['Ubuntu', 'Raspberry Pi', 'SymBox', 'Docker'])) {
            $r[] = $this->Translate('supported OS');
        }

        for ($cnt = 0; $cnt < self::$NUM_DEVICE; $cnt++) {
            $device = $this->ReadPropertyString('disk' . $cnt . '_device');
            if ($device != '') {
                $data = exec('hddtemp --version 2>&1', $output, $exitcode);
                if ($exitcode != 0) {
                    $r[] = 'hddtemp';
                }
                break;
            }
        }

        if ($r != []) {
            $s = $this->Translate('The following system prerequisites are missing') . ': ' . implode(', ', $r);
        }

        return $s;
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $vpos = 0;
        // Hostname
        $this->MaintainVariable('Hostname', $this->Translate('Hostname'), VARIABLETYPE_STRING, '', $vpos++, true);
        // OS-Version
        $this->MaintainVariable('OsVersion', $this->Translate('Operating system'), VARIABLETYPE_STRING, '', $vpos++, true);
        // Uptime
        $this->MaintainVariable('Uptime', $this->Translate('Uptime'), VARIABLETYPE_INTEGER, 'ServerInfo.Duration', $vpos++, true);
        $this->MaintainVariable('Uptime_Pretty', $this->Translate('Uptime'), VARIABLETYPE_STRING, '', $vpos++, true);
        // Load
        $this->MaintainVariable('Load1m', $this->Translate('Load of last 1 min'), VARIABLETYPE_FLOAT, 'ServerInfo.Load', $vpos++, true);
        $this->MaintainVariable('Load5m', $this->Translate('Load of last 5 min'), VARIABLETYPE_FLOAT, 'ServerInfo.Load', $vpos++, true);
        $this->MaintainVariable('Load15m', $this->Translate('Load of last 15 min'), VARIABLETYPE_FLOAT, 'ServerInfo.Load', $vpos++, true);
        $this->MaintainVariable('ProcRunnable', $this->Translate('Count of runable processes'), VARIABLETYPE_INTEGER, '', $vpos++, true);
        $this->MaintainVariable('ProcTotal', $this->Translate('Count of all processes'), VARIABLETYPE_INTEGER, '', $vpos++, true);
        // Memory
        $this->MaintainVariable('MemTotal', $this->Translate('Total memory'), VARIABLETYPE_FLOAT, 'ServerInfo.MB', $vpos++, true);
        $this->MaintainVariable('MemFree', $this->Translate('Free memory'), VARIABLETYPE_FLOAT, 'ServerInfo.MB', $vpos++, true);
        $this->MaintainVariable('MemAvailable', $this->Translate('Available memory'), VARIABLETYPE_FLOAT, 'ServerInfo.MB', $vpos++, true);
        // CPU
        $this->MaintainVariable('CpuModel', $this->Translate('Model of cpu'), VARIABLETYPE_STRING, '', $vpos++, true);
        $this->MaintainVariable('CpuCurFrequency', $this->Translate('Current cpu-frequency'), VARIABLETYPE_INTEGER, 'ServerInfo.Frequency', $vpos++, true);
        $this->MaintainVariable('CpuCount', $this->Translate('Number of cpu-cores'), VARIABLETYPE_INTEGER, '', $vpos++, true);
        $this->MaintainVariable('CpuUsage', $this->Translate('Usage of cpu'), VARIABLETYPE_FLOAT, 'ServerInfo.Usage', $vpos++, true);
        // Temperatur
        $this->MaintainVariable('CpuTemp', $this->Translate('Temperatur of cpu'), VARIABLETYPE_FLOAT, 'ServerInfo.Temperature', $vpos++, true);

        $cntName = ['1st', '2nd', '3rd', '4th'];
        for ($cnt = 0; $cnt < self::$NUM_DEVICE; $cnt++) {
            $dev = $this->ReadPropertyString('disk' . $cnt . '_device');
            $pfx = 'Disk' . $cnt;
            $cn = $cntName[$cnt];
            $this->MaintainVariable($pfx . 'Temp', $this->Translate('Temperatur of ' . $cn . ' disk'), VARIABLETYPE_FLOAT, 'ServerInfo.Temperature', $vpos++, $dev != '');
        }

        for ($cnt = 0; $cnt < self::$NUM_DEVICE; $cnt++) {
            $dev = $this->ReadPropertyString('partition' . $cnt . '_device');
            $pfx = 'Partition' . $cnt;
            $cn = $cntName[$cnt];
            $this->MaintainVariable($pfx . 'Mountpoint', $this->Translate('Mountpoint of ' . $cn . ' partition'), VARIABLETYPE_STRING, '', $vpos++, $dev != '');
            $this->MaintainVariable($pfx . 'Size', $this->Translate('Size of ' . $cn . ' partition'), VARIABLETYPE_FLOAT, 'ServerInfo.GB', $vpos++, $dev != '');
            $this->MaintainVariable($pfx . 'Used', $this->Translate('Used space of ' . $cn . ' partition'), VARIABLETYPE_FLOAT, 'ServerInfo.GB', $vpos++, $dev != '');
            $this->MaintainVariable($pfx . 'Available', $this->Translate('Available space of ' . $cn . ' partition'), VARIABLETYPE_FLOAT, 'ServerInfo.GB', $vpos++, $dev != '');
            $this->MaintainVariable($pfx . 'Usage', $this->Translate('Usage of ' . $cn . ' partition'), VARIABLETYPE_FLOAT, 'ServerInfo.Usage', $vpos++, $dev != '');
        }

        $this->MaintainVariable('LastUpdate', $this->Translate('Last update'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);

        $s = $this->CheckPrerequisites();
        if ($s != '') {
            $this->SetTimerInterval('UpdateData', 0);
            $this->SetStatus(self::$IS_INVALIDPREREQUISITES);
            $this->LogMessage($s, KL_WARNING);
            return;
        }

        $module_disable = $this->ReadPropertyBoolean('module_disable');
        if ($module_disable) {
            $this->SetTimerInterval('UpdateData', 0);
            $this->SetStatus(IS_INACTIVE);
            return;
        }

        $this->SetStatus(IS_ACTIVE);
        $this->SetUpdateInterval();
    }

    public function GetConfigurationForm()
    {
        $formElements = $this->GetFormElements();
        $formActions = $this->GetFormActions();
        $formStatus = $this->GetFormStatus();

        $form = json_encode(['elements' => $formElements, 'actions' => $formActions, 'status' => $formStatus]);
        if ($form == '') {
            $this->SendDebug(__FUNCTION__, 'json_error=' . json_last_error_msg(), 0);
            $this->SendDebug(__FUNCTION__, '=> formElements=' . print_r($formElements, true), 0);
            $this->SendDebug(__FUNCTION__, '=> formActions=' . print_r($formActions, true), 0);
            $this->SendDebug(__FUNCTION__, '=> formStatus=' . print_r($formStatus, true), 0);
        }
        return $form;
    }

    private function GetFormElements()
    {
        $formElements = [];

        $s = $this->CheckPrerequisites();
        if ($s != '') {
            $formElements[] = [
                'type'    => 'Label',
                'caption' => $s
            ];
        }

        $formElements[] = [
            'type'    => 'CheckBox',
            'name'    => 'module_disable',
            'caption' => 'Instance is disabled'
        ];

        $cntName = ['1st', '2nd', '3rd', '4th'];

        $formElements[] = [
            'type'    => 'Label',
            'caption' => 'Partitions to be monitored'
        ];
        for ($cnt = 0; $cnt < self::$NUM_DEVICE; $cnt++) {
            $formElements[] = [
                'type'    => 'ValidationTextBox',
                'name'    => 'partition' . $cnt . '_device',
                'caption' => $cntName[$cnt] . ' partition'
            ];
        }

        $formElements[] = [
            'type'    => 'Label',
            'caption' => 'Disks to be monitored'
        ];
        for ($cnt = 0; $cnt < self::$NUM_DEVICE; $cnt++) {
            $formElements[] = [
                'type'    => 'ValidationTextBox',
                'name'    => 'disk' . $cnt . '_device',
                'caption' => $cntName[$cnt] . ' disk'
            ];
        }

        $formElements[] = [
            'type'    => 'Label',
            'caption' => 'Update data every X minutes'
        ];
        $formElements[] = [
            'type'    => 'IntervalBox',
            'name'    => 'update_interval',
            'caption' => 'Minutes'
        ];

        return $formElements;
    }

    private function GetFormActions()
    {
        $formActions = [];

        $formActions[] = [
            'type'    => 'Button',
            'caption' => 'Update data',
            'onClick' => 'ServerInfo_UpdateData($id);'
        ];

        return $formActions;
    }

    protected function SetUpdateInterval()
    {
        $min = $this->ReadPropertyInteger('update_interval');
        $msec = $min > 0 ? $min * 1000 * 60 : 0;
        $this->SetTimerInterval('UpdateData', $msec);
    }

    public function UpdateData()
    {
        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return;
        }

        $this->get_hostname();
        $this->get_version();
        $this->get_memory();
        $this->get_load();
        $this->get_uptime();
        $this->get_partition();
        $this->get_cputemp();
        $this->get_hddtemp();
        $this->get_cpuinfo();
        $this->get_cpuload();

        $this->SetValue('LastUpdate', time());
    }

    private function execute($cmd)
    {
        $this->SendDebug(__FUNCTION__, 'cmd="' . $cmd . '"', 0);

        $time_start = microtime(true);
        $data = exec($cmd, $output, $exitcode);
        $duration = round(microtime(true) - $time_start, 2);

        if ($exitcode) {
            $ok = false;
            $err = $data;
            $output = '';
        } else {
            $ok = true;
            $err = '';
        }

        $this->SendDebug(__FUNCTION__, ' ... duration=' . $duration . ', exitcode=' . $exitcode . ', status=' . ($ok ? 'ok' : 'fail') . ', err=' . $err, 0);
        $this->SendDebug(__FUNCTION__, ' ... output=' . utf8_decode(print_r($output, true)), 0);

        return $output;
    }

    private function get_hostname()
    {
        $res = $this->execute('hostname');
        if ($res == '' || count($res) < 1) {
            $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($res, true), 0);
            return false;
        }

        $Hostname = $res[0];

        $this->SendDebug(__FUNCTION__, 'Hostname=' . $Hostname, 0);
        $this->SetValue('Hostname', $Hostname);

        return true;
    }

    private function get_version()
    {
        $sys = IPS_GetKernelPlatform();
        switch ($sys) {
        case 'Ubuntu':
        case 'Raspberry Pi':
            $res = $this->execute('lsb_release -ds');
            if ($res == '' || count($res) < 1) {
                $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($res, true), 0);
                return false;
            }
            $OsVersion = $res[0];
            break;
        case 'SymBox':
            $res = $this->execute('uname -a');
            if ($res == '' || count($res) < 1) {
                $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($res, true), 0);
                return false;
            }
            $r = explode(' ', $res[0]);
            if ($r == '' || count($r) < 3) {
                $this->SendDebug(__FUNCTION__, 'unknwon data format: ' . print_r($res, true), 0);
                $OsVersion = $res;
            } else {
                $OsVersion = $r[1] . ' ' . $r[2];
            }
            break;
        case 'Docker':
            $res = $this->execute('cat /etc/os-release');
            if ($res == '' || count($res) < 1) {
                $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($res, true), 0);
                return false;
            }
            if ($res == '' || count($res) < 3) {
                $this->SendDebug(__FUNCTION__, 'unknwon data format: ' . print_r($res, true), 0);
                $OsVersion = $res;
            } else {
                $OsVersion = $res[0] . ' ' . $res[1];
            }
            break;
        default:
            $this->SendDebug(__FUNCTION__, 'unsuported OS ' . $sys, 0);
            return false;
            break;
        }

        $this->SendDebug(__FUNCTION__, 'OsVersion=' . $OsVersion, 0);
        $this->SetValue('OsVersion', $OsVersion);

        return true;
    }

    private function get_memory()
    {
        $res = $this->execute('cat /proc/meminfo');
        if ($res == '' || count($res) < 1) {
            $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($res, true), 0);
            return false;
        }

        $v = [];
        foreach ($res as $r) {
            $s = preg_split("/[:\s]+/", $r);
            if (count($s) < 2) {
                $this->SendDebug(__FUNCTION__, 'bad data: ' . $r, 0);
                continue;
            }
            $name = $s[0];
            $size = $s[1];
            $unit = isset($s[2]) ? $s[2] : '';
            switch (strtolower($unit)) {
                case 'kb':
                    $size /= 1024;
                    break;
                case 'mb':
                    break;
                case 'mb':
                    $size *= 1024;
                    break;
                default:
                    break;
            }
            $v[$name] = $size;
        }

        $MemTotal = isset($v['MemTotal']) ? $v['MemTotal'] : 0;
        $MemFree = isset($v['MemFree']) ? $v['MemFree'] : 0;
        $MemAvailable = isset($v['MemAvailable']) ? $v['MemAvailable'] : 0;

        $this->SendDebug(__FUNCTION__, 'MemTotal=' . $MemTotal . ', MemFree=' . $MemFree . ', MemAvailable=' . $MemAvailable, 0);
        $this->SetValue('MemTotal', $MemTotal);
        $this->SetValue('MemFree', $MemFree);
        $this->SetValue('MemAvailable', $MemAvailable);

        return true;
    }

    private function get_load()
    {
        // load-1m, 5m, 15m, run.proc/total.proc, last-pid
        $res = $this->execute('cat /proc/loadavg');
        if ($res == '' || count($res) < 1) {
            $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($res, true), 0);
            return false;
        }
        $r = explode(' ', $res[0]);
        if (count($r) < 4) {
            $this->SendDebug(__FUNCTION__, 'bad data: ' . $res[0], 0);
            return false;
        }

        $Load1m = $r[0];
        $Load5m = $r[1];
        $Load15m = $r[2];

        $s = explode('/', $r[3]);
        if (count($s) < 2) {
            $this->SendDebug(__FUNCTION__, 'bad data: ' . $r[3], 0);
            return false;
        }

        $ProcRunnable = $s[0];
        $ProcTotal = $s[1];

        $this->SendDebug(__FUNCTION__, 'Load1m=' . $Load1m . ', Load5m=' . $Load5m . ', Load15m=' . $Load15m . ', ProcRunnable=' . $ProcRunnable . ', ProcTotal=' . $ProcTotal, 0);
        $this->SetValue('Load1m', $Load1m);
        $this->SetValue('Load5m', $Load5m);
        $this->SetValue('Load15m', $Load15m);
        $this->SetValue('ProcRunnable', $ProcRunnable);
        $this->SetValue('ProcTotal', $ProcTotal);

        return true;
    }

    private function get_uptime()
    {
        // uptime in sec, idle-time (overall)
        $res = $this->execute('cat /proc/uptime');
        if ($res == '' || count($res) < 1) {
            $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($res, true), 0);
            return false;
        }
        $r = explode(' ', $res[0]);
        if (count($r) < 1) {
            $this->SendDebug(__FUNCTION__, 'bad data: ' . $res[0], 0);
            return false;
        }

        $sec = $r[0];

        $Uptime = $sec;
        $Uptime_Pretty = '';
        if ($sec > 84600) {
            $day = floor($sec / 84600);
            $sec = $sec % 84600;

            $sec -= floor($sec % 60);
            if ($day > 3) {
                $sec -= floor($sec % 3600);
            }
            if ($day > 10) {
                $sec -= floor($sec % 86400);
            }

            $Uptime_Pretty .= sprintf('%dd', $day);
        }
        if ($sec > 3600) {
            $hour = floor($sec / 3600);
            $sec = $sec % 3600;

            $Uptime_Pretty .= sprintf('%dh', $hour);
        }
        if ($sec > 60) {
            $min = floor($sec / 60);
            $sec = $sec % 60;

            $Uptime_Pretty .= sprintf('%dm', $min);
        }
        if ($sec > 0) {
            $Uptime_Pretty .= sprintf('%ds', $sec);
        }

        $this->SendDebug(__FUNCTION__, 'Uptime=' . $Uptime . ' sec => ' . $Uptime_Pretty, 0);
        $this->SetValue('Uptime', $Uptime);
        $this->SetValue('Uptime_Pretty', $Uptime_Pretty);

        return true;
    }

    private function get_partition()
    {
        for ($cnt = 0; $cnt < self::$NUM_DEVICE; $cnt++) {
            $device = $this->ReadPropertyString('partition' . $cnt . '_device');
            if ($device == '') {
                continue;
            }

            $Mountpoint = '';
            $Size = 0;
            $Used = 0;
            $Available = 0;
            $Usage = 0;

            $res = $this->execute('df');
            if ($res == '' || count($res) < 1) {
                $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($res, true), 0);
                return false;
            }
            foreach ($res as $r) {
                $s = preg_split("/[\s]+/", $r);
                if (count($s) < 6) {
                    $this->SendDebug(__FUNCTION__, 'bad data: ' . $r, 0);
                    continue;
                }
                if ($s[0] == $device) {
                    $Size = floor($s[1] / (1024 * 1024) * 10) / 10;
                    $Used = floor($s[2] / (1024 * 1024) * 10) / 10;
                    $Available = floor($s[3] / (1024 * 1024) * 10) / 10;
                    if (preg_match('/([\d]*)/', $s[4], $q)) {
                        $Usage = $q[1];
                    }
                    $Mountpoint = $s[5];
                }
            }

            $this->SendDebug(__FUNCTION__, 'partition ' . $cnt . '=' . $device . ': size=' . $Size . ' GB, used=' . $Used . ' GB, available=' . $Available . ' GB, ' . $Usage . '%' . ', mountpoint=' . $Mountpoint, 0);
            $this->SetValue('Partition' . $cnt . 'Mountpoint', $Mountpoint);
            $this->SetValue('Partition' . $cnt . 'Size', $Size);
            $this->SetValue('Partition' . $cnt . 'Used', $Used);
            $this->SetValue('Partition' . $cnt . 'Available', $Available);
            $this->SetValue('Partition' . $cnt . 'Usage', $Usage);
        }

        return true;
    }

    private function get_cputemp()
    {
        // x86_pkg_temp
        $res1 = $this->execute('cat `echo /sys/class/thermal/thermal_zone*/type`');
        if ($res1 == '' || count($res1) < 1) {
            $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($res1, true), 0);
            return false;
        }
        $res2 = $this->execute('cat `echo /sys/class/thermal/thermal_zone*/temp`');
        if ($res2 == '' || count($res2) < 1) {
            $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($res2, true), 0);
            return false;
        }

        $CpuTemp = 0;
        for ($i = 0; $i < min(count($res1), count($res2)); $i++) {
            $t = floor($res2[$i] / 1000);
            $this->SendDebug(__FUNCTION__, ' ... type=' . $res1[$i] . ', temp=' . $t, 0);
            if ($t > $CpuTemp) {
                $CpuTemp = $t;
            }
        }

        $this->SendDebug(__FUNCTION__, 'CpuTemp=' . $CpuTemp, 0);
        $this->SetValue('CpuTemp', $CpuTemp);

        return true;
    }

    private function get_hddtemp()
    {
        for ($cnt = 0; $cnt < self::$NUM_DEVICE; $cnt++) {
            $device = $this->ReadPropertyString('disk' . $cnt . '_device');
            if ($device == '') {
                continue;
            }

            $res = $this->execute('hddtemp ' . $device);
            if ($res == '' || count($res) < 1) {
                $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($res, true), 0);
                return false;
            }

            $Temp = 0;

            $s = preg_split("/:[\s]+/", $res[0]);
            if (preg_match('/([\d]*)/', $s[2], $q)) {
                $Temp = $q[1];
            }

            $this->SendDebug(__FUNCTION__, 'disk' . $cnt . '=' . $device . ': Temp=' . $Temp, 0);
            $this->SetValue('Disk' . $cnt . 'Temp', $Temp);
        }

        return true;
    }

    private function get_cpuinfo()
    {
        $CpuModel = '';
        $CpuCount = 0;
        $CpuCurFrequency = 0;

        $sys = IPS_GetKernelPlatform();
        switch ($sys) {
        case 'Ubuntu':
        case 'Raspberry Pi':
        case 'Docker':
            $res = $this->execute('lscpu');
            if ($res == '' || count($res) < 1) {
                $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($res, true), 0);
                return false;
            }
            $v = [];
            foreach ($res as $r) {
                $s = preg_split("/:[\s]+/", $r);
                if (count($s) < 2) {
                    $this->SendDebug(__FUNCTION__, 'bad data: ' . $r, 0);
                    continue;
                }
                $name = $s[0];
                $value = $s[1];
                $v[$name] = $value;
            }
            switch ($sys) {
            case 'Ubuntu':
                $CpuModel = isset($v['Modellname']) ? $v['Modellname'] : '';
                $CpuCount = isset($v['CPU(s)']) ? $v['CPU(s)'] : 0;
                $CpuCurFrequency = isset($v['CPU MHz']) ? $v['CPU MHz'] : 0;
                break;
            case 'Raspberry Pi':
                $CpuModel = isset($v['Model name']) ? $v['Model name'] : '';
                $CpuCount = isset($v['CPU(s)']) ? $v['CPU(s)'] : 0;
                $res = $this->execute('vcgencmd measure_clock arm ');
                if ($res == '' || count($res) < 1) {
                    $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($res, true), 0);
                    return false;
                }
                $s = preg_split('/=/', $res[0]);
                if (preg_match('/^frequency/', $s[0])) {
                    $CpuCurFrequency = (int) ((float) $s[1] / 1024 / 1024);
                }
                break;
            case 'Docker':
                $CpuModel = isset($v['Model name']) ? $v['Model name'] : '';
                $CpuCount = isset($v['CPU(s)']) ? $v['CPU(s)'] : 0;
                $CpuCurFrequency = isset($v['CPU MHz']) ? $v['CPU MHz'] : 0;
                break;
            }
            break;
        case 'SymBox':
            $res = $this->execute('cat /proc/cpuinfo');
            if ($res == '' || count($res) < 1) {
                $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($res, true), 0);
                return false;
            }
            foreach ($res as $r) {
                $s = preg_split("/[\s]+:[\s]+/", $r);
                if (count($s) < 2) {
                    continue;
                }
                switch ($s[0]) {
                    case 'model name':
                        if ($CpuCount == 1) {
                            $CpuModel = $s[1];
                        }
                        break;
                    case 'processor':
                        $CpuCount++;
                        break;
                    default:
                        break;
                }
            }
            $res = $this->execute('vcgencmd measure_clock arm ');
            if ($res == '' || count($res) < 1) {
                $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($res, true), 0);
                return false;
            }
            $s = preg_split('/=/', $res[0]);
            if (preg_match('/^frequency/', $s[0])) {
                $CpuCurFrequency = (int) ((float) $s[1] / 1024 / 1024);
            }
            break;
        default:
            $this->SendDebug(__FUNCTION__, 'unsuported OS ' . $sys, 0);
            return false;
            break;
        }

        $this->SendDebug(__FUNCTION__, 'CpuModel=' . $CpuModel . ', CpuCurFrequency=' . $CpuCurFrequency . ' MHz, CpuCount=' . $CpuCount, 0);
        $this->SetValue('CpuModel', $CpuModel);
        $this->SetValue('CpuCurFrequency', $CpuCurFrequency);
        $this->SetValue('CpuCount', $CpuCount);

        return true;
    }

    private function get_cpuload()
    {
        $CpuLoad = 0;

        $res = $this->execute('cat /proc/stat');
        if ($res == '' || count($res) < 1) {
            $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($res, true), 0);
            return false;
        }
        foreach ($res as $r) {
            $s = preg_split("/[\s]+/", $r);
            if (count($s) < 2) {
                $this->SendDebug(__FUNCTION__, 'bad data: ' . $r, 0);
                continue;
            }

            if ($s[0] == 'cpu') {
                $prev_total = floatval($this->GetBuffer('prev_total'));
                $prev_idle = floatval($this->GetBuffer('prev_idle'));

                $idle = floatval($s[4]);
                $iowait = floatval($s[5]);
                $sum_idle = $idle + $iowait;
                $this->SendDebug(__FUNCTION__, 'idle=' . $idle . ', iowait=' . $iowait . ' => sum_idle=' . $sum_idle, 0);

                $user = floatval($s[1]);
                $nice = floatval($s[2]);
                $system = floatval($s[3]);
                $irq = floatval($s[6]);
                $softrig = floatval($s[7]);
                $steal = floatval($s[8]);
                $sum_busy = $user + $nice + $system + $irq + $softrig + $steal;
                $this->SendDebug(__FUNCTION__, 'user=' . $user . ', nice=' . $nice . ', system=' . $system . ', irq=' . $irq . ', softrig=' . $softrig . ', steal=' . $steal . ' => sum_busy=' . $sum_busy, 0);

                $total = $sum_idle + $sum_busy;
                // Differenzen berechnen
                $diff_total = $total - $prev_total;
                $diff_idle = $sum_idle - $prev_idle;

                // Auslastung berechnen
                // Wert nur ausgeben, wenn der Buffer schon einmal mit den aktuellen Werten beschrieben wurde
                if (($prev_total + $prev_idle) > 0) {
                    $CpuUsage = (($diff_total - $diff_idle) / $diff_total) * 100;
                } else {
                    $CpuUsage = 0;
                }
                $this->SendDebug(__FUNCTION__, 'total=' . $total . ', sum_idle=' . $sum_idle . ', diff_total=' . $diff_total . ', diff_idle=' . $diff_idle, 0);

                // Aktuelle Werte für die nächste Berechnung in den Buffer schreiben
                $this->SetBuffer('prev_total', $total);
                $this->SetBuffer('prev_idle', $sum_idle);

                break;
            }
        }

        $this->SendDebug(__FUNCTION__, 'CpuUsage=' . $CpuUsage, 0);
        $this->SetValue('CpuUsage', $CpuUsage);

        return true;
    }
}
