<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';
require_once __DIR__ . '/../libs/local.php';

class ServerInfo extends IPSModule
{
    use ServerInfo\StubsCommonLib;
    use ServerInfoLocalLib;

    public static $NUM_DEVICE = 4;

    public function __construct(string $InstanceID)
    {
        parent::__construct($InstanceID);

        $this->CommonConstruct(__DIR__);
    }

    public function __destruct()
    {
        $this->CommonDestruct();
    }

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyBoolean('module_disable', false);

        $this->RegisterPropertyBoolean('with_swap', true);
        $this->RegisterPropertyBoolean('with_cputemp', true);
        $this->RegisterPropertyBoolean('with_hddtemp', true);
        $this->RegisterPropertyBoolean('with_symcon', true);

        $this->RegisterPropertyString('partition0_device', '');
        $this->RegisterPropertyInteger('partition0_unit', self::$UNIT_GB);
        $this->RegisterPropertyString('partition1_device', '');
        $this->RegisterPropertyInteger('partition1_unit', self::$UNIT_GB);
        $this->RegisterPropertyString('partition2_device', '');
        $this->RegisterPropertyInteger('partition2_unit', self::$UNIT_GB);
        $this->RegisterPropertyString('partition3_device', '');
        $this->RegisterPropertyInteger('partition3_unit', self::$UNIT_GB);
        $this->RegisterPropertyString('disk0_device', '');
        $this->RegisterPropertyString('disk1_device', '');
        $this->RegisterPropertyString('disk2_device', '');
        $this->RegisterPropertyString('disk3_device', '');

        $this->RegisterPropertyInteger('update_interval', '0');

        $this->RegisterAttributeString('UpdateInfo', json_encode([]));
        $this->RegisterAttributeString('ModuleStats', json_encode([]));

        $this->InstallVarProfiles(false);

        $this->RegisterTimer('UpdateData', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "UpdateData", "");');

        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    public function MessageSink($tstamp, $senderID, $message, $data)
    {
        parent::MessageSink($tstamp, $senderID, $message, $data);

        if ($message == IPS_KERNELMESSAGE && $data[0] == KR_READY) {
            $this->SetUpdateInterval();
        }
    }

    private function CheckModulePrerequisites()
    {
        $r = [];

        if (IPS_GetKernelVersion() >= 6) {
            $sysList = ['Ubuntu', 'Raspberry Pi', 'SymBox', 'Ubuntu (Docker)', 'Raspberry Pi (Docker)'];
        } else {
            $sysList = ['Ubuntu', 'Raspberry Pi', 'SymBox', 'Docker'];
        }
        $sys = IPS_GetKernelPlatform();
        if (!in_array($sys, $sysList)) {
            $r[] = $this->Translate('supported OS (at this moment only: ') . implode(', ', $sysList) . ')';
        }

        $with_hddtemp = $this->ReadPropertyBoolean('with_hddtemp');
        if ($with_hddtemp) {
            for ($cnt = 0; $cnt < self::$NUM_DEVICE; $cnt++) {
                $device = $this->ReadPropertyString('disk' . $cnt . '_device');
                if ($device != '') {
                    $data = exec('hddtemp --version 2>&1', $output, $exitcode);
                    if ($exitcode != 0) {
                        $r[] = $this->Translate('missing utility "hddtemp"');
                    }
                    break;
                }
            }
        }

        return $r;
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->MaintainReferences();

        if ($this->CheckPrerequisites() != false) {
            $this->MaintainTimer('UpdateData', 0);
            $this->MaintainStatus(self::$IS_INVALIDPREREQUISITES);
            return;
        }

        if ($this->CheckUpdate() != false) {
            $this->MaintainTimer('UpdateData', 0);
            $this->MaintainStatus(self::$IS_UPDATEUNCOMPLETED);
            return;
        }

        if ($this->CheckConfiguration() != false) {
            $this->MaintainTimer('UpdateData', 0);
            $this->MaintainStatus(self::$IS_INVALIDCONFIG);
            return;
        }

        $with_swap = $this->ReadPropertyBoolean('with_swap');
        $with_cputemp = $this->ReadPropertyBoolean('with_cputemp');
        $with_hddtemp = $this->ReadPropertyBoolean('with_hddtemp');
        $with_symcon = $this->ReadPropertyBoolean('with_symcon');

        $sys = IPS_GetKernelPlatform();
        switch ($sys) {
            case 'Ubuntu':
                $with_daemon_cpuusage = $with_symcon;
                break;
            case 'Raspberry Pi':
                $with_daemon_cpuusage = $with_symcon;
                break;
            case 'Docker':
            case 'Ubuntu (Docker)':
            case 'Raspberry Pi (Docker)':
                $with_daemon_cpuusage = false;
                break;
            case 'SymBox':
                $with_daemon_cpuusage = $with_symcon;
                break;
            default:
                $with_daemon_cpuusage = false;
                break;
        }

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
        // Swap
        $this->MaintainVariable('SwapTotal', $this->Translate('Total swap-space'), VARIABLETYPE_FLOAT, 'ServerInfo.MB', $vpos++, $with_swap);
        $this->MaintainVariable('SwapFree', $this->Translate('Free swap-space'), VARIABLETYPE_FLOAT, 'ServerInfo.MB', $vpos++, $with_swap);
        // CPU
        $this->MaintainVariable('CpuModel', $this->Translate('Model of cpu'), VARIABLETYPE_STRING, '', $vpos++, true);
        $this->MaintainVariable('CpuCurFrequency', $this->Translate('Current cpu-frequency'), VARIABLETYPE_INTEGER, 'ServerInfo.Frequency', $vpos++, true);
        $this->MaintainVariable('CpuCount', $this->Translate('Number of cpu-cores'), VARIABLETYPE_INTEGER, '', $vpos++, true);
        $this->MaintainVariable('CpuUsage', $this->Translate('Usage of cpu'), VARIABLETYPE_FLOAT, 'ServerInfo.Usage', $vpos++, true);
        // Temperatur
        $this->MaintainVariable('CpuTemp', $this->Translate('Temperatur of cpu'), VARIABLETYPE_FLOAT, 'ServerInfo.Temperature', $vpos++, $with_cputemp);

		$cntName = ['1st', '2nd', '3rd', '4th'];

        if ($with_hddtemp) {
            for ($cnt = 0; $cnt < self::$NUM_DEVICE; $cnt++) {
                $dev = $this->ReadPropertyString('disk' . $cnt . '_device');
                $pfx = 'Disk' . $cnt;
                $cn = $cntName[$cnt];
                $this->MaintainVariable($pfx . 'Temp', $this->Translate('Temperatur of ' . $cn . ' disk'), VARIABLETYPE_FLOAT, 'ServerInfo.Temperature', $vpos++, $dev != '');
            }
        }

        for ($cnt = 0; $cnt < self::$NUM_DEVICE; $cnt++) {
            $dev = $this->ReadPropertyString('partition' . $cnt . '_device');
            $unit = $this->ReadPropertyInteger('partition' . $cnt . '_unit');
            $varprof = $this->Unit2Varprof($unit);
            $pfx = 'Partition' . $cnt;
            $cn = $cntName[$cnt];
            $this->MaintainVariable($pfx . 'Mountpoint', $this->Translate('Mountpoint of ' . $cn . ' partition'), VARIABLETYPE_STRING, '', $vpos++, $dev != '');
            $this->MaintainVariable($pfx . 'Size', $this->Translate('Size of ' . $cn . ' partition'), VARIABLETYPE_FLOAT, $varprof, $vpos++, $dev != '');
            $this->MaintainVariable($pfx . 'Used', $this->Translate('Used space of ' . $cn . ' partition'), VARIABLETYPE_FLOAT, $varprof, $vpos++, $dev != '');
            $this->MaintainVariable($pfx . 'Available', $this->Translate('Available space of ' . $cn . ' partition'), VARIABLETYPE_FLOAT, $varprof, $vpos++, $dev != '');
            $this->MaintainVariable($pfx . 'Usage', $this->Translate('Usage of ' . $cn . ' partition'), VARIABLETYPE_FLOAT, 'ServerInfo.Usage', $vpos++, $dev != '');
        }

        // Symcon daemon
        $vpos = 100;
        $this->MaintainVariable('Daemon_Running', $this->Translate('Daemon: running'), VARIABLETYPE_INTEGER, 'ServerInfo.Duration', $vpos++, $with_symcon);
        $this->MaintainVariable('Daemon_Running_Pretty', $this->Translate('Daemon: running'), VARIABLETYPE_STRING, '', $vpos++, $with_symcon);

        $this->MaintainVariable('Daemon_MemSize', $this->Translate('Daemon: process size'), VARIABLETYPE_FLOAT, 'ServerInfo.MB', $vpos++, $with_symcon);
        $this->MaintainVariable('Daemon_MemResident', $this->Translate('Daemon: resident process size'), VARIABLETYPE_FLOAT, 'ServerInfo.MB', $vpos++, $with_symcon);

        $this->MaintainVariable('Daemon_CpuUsedTotal', $this->Translate('Daemon: CPU time since start'), VARIABLETYPE_INTEGER, 'ServerInfo.Duration', $vpos++, $with_symcon);
        $this->MaintainVariable('Daemon_CpuUsedHourly', $this->Translate('Daemon: CPU time per hour'), VARIABLETYPE_INTEGER, 'ServerInfo.Duration', $vpos++, $with_symcon);
        $this->MaintainVariable('Daemon_CpuUsage', $this->Translate('Daemon: cpu usage'), VARIABLETYPE_FLOAT, 'ServerInfo.Usage', $vpos++, $with_daemon_cpuusage);

        $vpos = 200;
        $this->MaintainVariable('LastUpdate', $this->Translate('Last update'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);

        $module_disable = $this->ReadPropertyBoolean('module_disable');
        if ($module_disable) {
            $this->MaintainTimer('UpdateData', 0);
            $this->MaintainStatus(IS_INACTIVE);
            return;
        }

        $this->MaintainStatus(IS_ACTIVE);

        if (IPS_GetKernelRunlevel() == KR_READY) {
            $this->SetUpdateInterval();
        }
    }

    private function GetFormElements()
    {
        $formElements = $this->GetCommonFormElements('Server information');

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            return $formElements;
        }

        $formElements[] = [
            'name'    => 'module_disable',
            'type'    => 'CheckBox',
            'caption' => 'Disable instance',
        ];

        $cntName = ['1st', '2nd', '3rd', '4th'];

        $items = [];
        for ($cnt = 0; $cnt < self::$NUM_DEVICE; $cnt++) {
            $items[] = [
                'type'    => 'RowLayout',
                'items'   => [
                    [
                        'type'    => 'Label',
                        'caption' => $cntName[$cnt] . ' partition',
                    ],
                    [
                        'type'    => 'ValidationTextBox',
                        'name'    => 'partition' . $cnt . '_device',
                        'caption' => 'device',
                        'width'   => '500px',
                    ],
                    [
                        'type'    => 'Select',
                        'options' => $this->UnitAsOptions(),
                        'name'    => 'partition' . $cnt . '_unit',
                        'caption' => 'unit',
                        'width'   => '100px',
                    ],
                ],
            ];
        }
        $formElements[] = [
            'type'      => 'ExpansionPanel',
            'items'     => $items,
            'expanded'  => false,
            'caption'   => 'Partitions to be monitored',
        ];

        $items = [];
        for ($cnt = 0; $cnt < self::$NUM_DEVICE; $cnt++) {
            $items[] = [
                'type'    => 'ValidationTextBox',
                'name'    => 'disk' . $cnt . '_device',
                'caption' => $cntName[$cnt] . ' disk'
            ];
        }
        $formElements[] = [
            'type'      => 'ExpansionPanel',
            'items'     => $items,
            'expanded'  => false,
            'caption'   => 'Disks to be monitored',
        ];

        $formElements[] = [
            'type'    => 'CheckBox',
            'name'    => 'with_swap',
            'caption' => 'Show swap-space'
        ];

        $formElements[] = [
            'type'    => 'CheckBox',
            'name'    => 'with_cputemp',
            'caption' => 'Show cpu-temperature'
        ];

        $formElements[] = [
            'type'    => 'CheckBox',
            'name'    => 'with_hddtemp',
            'caption' => 'Show hdd-temperature'
        ];

        $formElements[] = [
            'type'    => 'CheckBox',
            'name'    => 'with_symcon',
            'caption' => 'Collate information of the symcon daemon'
        ];

        $formElements[] = [
            'name'    => 'update_interval',
            'type'    => 'NumberSpinner',
            'minimum' => 0,
            'suffix'  => 'Minutes',
            'caption' => 'Update interval'
        ];

        return $formElements;
    }

    private function GetFormActions()
    {
        $formActions = [];

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            $formActions[] = $this->GetCompleteUpdateFormAction();

            $formActions[] = $this->GetInformationFormAction();
            $formActions[] = $this->GetReferencesFormAction();

            return $formActions;
        }

        $formActions[] = [
            'type'    => 'Button',
            'caption' => 'Update data',
            'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "UpdateData", "");',
        ];

        $formActions[] = [
            'type'      => 'ExpansionPanel',
            'caption'   => 'Expert area',
            'expanded'  => false,
            'items'     => [
                $this->GetInstallVarProfilesFormItem(),
            ],
        ];

        $formActions[] = $this->GetInformationFormAction();
        $formActions[] = $this->GetReferencesFormAction();

        return $formActions;
    }

    public function RequestAction($ident, $value)
    {
        if ($this->CommonRequestAction($ident, $value)) {
            return;
        }
        switch ($ident) {
            case 'UpdateData':
                $this->UpdateData();
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'invalid ident ' . $ident, 0);
                break;
        }
    }

    private function SetUpdateInterval()
    {
        $min = $this->ReadPropertyInteger('update_interval');
        $msec = $min > 0 ? $min * 1000 * 60 : 0;
        $this->MaintainTimer('UpdateData', $msec);
    }

    private function UpdateData()
    {
        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return;
        }

        $this->SendDebug(__FUNCTION__, 'platform=' . IPS_GetKernelPlatform(), 0);

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
        $this->get_symcon();

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
        $this->SendDebug(__FUNCTION__, ' ... output=' . print_r($output, true), 0);

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
                $res = $this->execute('ls -t /mnt/system/symupd');
                if ($res == '' || count($res) < 1) {
                    $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($res, true), 0);
                    return false;
                }
                if (preg_match('/^symupd_(.*).md5$/', $res[0], $r)) {
                    $OsVersion = 'SymOS ' . $r[1];
                } else {
                    $this->SendDebug(__FUNCTION__, 'unknwon data format: ' . print_r($res, true), 0);
                    return false;
                }
                break;
            case 'Docker':
            case 'Ubuntu (Docker)':
            case 'Raspberry Pi (Docker)':
                $res = $this->execute('cat /etc/os-release');
                if ($res == '' || count($res) < 1) {
                    $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($res, true), 0);
                    return false;
                }
                $pretty_name = '';
                $name = '';
                foreach ($res as $r) {
                    if (preg_match('/^PRETTY_NAME="([^"]*)"/', $r, $q)) {
                        $pretty_name = $q[1];
                    }
                    if (preg_match('/^NAME="([^"]*)"/', $r, $q)) {
                        $name = $q[1];
                    }
                }
                if ($pretty_name != '') {
                    $OsVersion = $pretty_name;
                } elseif ($name != '') {
                    $OsVersion = $name;
                } else {
                    $OsVersion = $res;
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

        $with_swap = $this->ReadPropertyBoolean('with_swap');
        if ($with_swap) {
            $SwapTotal = isset($v['SwapTotal']) ? $v['SwapTotal'] : 0;
            $SwapFree = isset($v['SwapFree']) ? $v['SwapFree'] : 0;
            $this->SendDebug(__FUNCTION__, 'SwapTotal=' . $SwapTotal . ', SwapFree=' . $SwapFree, 0);
            $this->SetValue('SwapTotal', $SwapTotal);
            $this->SetValue('SwapFree', $SwapFree);
        }

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
        if ($sec > 86400) {
            $day = floor($sec / 86400);
            $sec = $sec % 86400;

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
            $unit = $this->ReadPropertyInteger('partition' . $cnt . '_unit');
            $factor = $this->Unit2Factor($unit);
            $unitS = $this->Unit2String($unit);

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
                $this->SendDebug(__FUNCTION__, ' ... ' . print_r($s, true), 0);

                if (count($s) < 6) {
                    $this->SendDebug(__FUNCTION__, 'bad data: ' . $r, 0);
                    continue;
                }
                if ($s[0] == $device) {
                    $Size = floor($s[1] / $factor * 10) / 10;
                    $Used = floor($s[2] / $factor * 10) / 10;
                    $Available = floor($s[3] / $factor * 10) / 10;
                    if (preg_match('/([\d]*)/', $s[4], $q)) {
                        $Usage = $q[1];
                    }
                    $Mountpoint = $s[5];
                }
            }

            $this->SendDebug(__FUNCTION__, 'partition ' . $cnt . '=' . $device . ': size=' . $Size . ' ' . $unitS . ', used=' . $Used . ' ' . $unitS . ', available=' . $Available . ' ' . $unitS . ', ' . $Usage . '%' . ', mountpoint=' . $Mountpoint, 0);
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
        $with_cputemp = $this->ReadPropertyBoolean('with_cputemp');
        if ($with_cputemp == false) {
            return true;
        }

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
        $with_hddtemp = $this->ReadPropertyBoolean('with_hddtemp');
        if ($with_hddtemp == false) {
            return true;
        }

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
            case 'Ubuntu (Docker)':
            case 'Raspberry Pi (Docker)':
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
                        if (isset($v['Modellname'])) {
                            $CpuModel = $v['Modellname'];
                        } elseif (isset($v['Model name'])) {
                            $CpuModel = $v['Model name'];
                        } else {
                            $CpuModel = '';
                        }
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
                    case 'Ubuntu (Docker)':
                    case 'Raspberry Pi (Docker)':
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

    private function get_symcon()
    {
        $with_symcon = $this->ReadPropertyBoolean('with_symcon');
        if ($with_symcon == false) {
            return true;
        }

        $time_start = IPS_GetKernelStartTime();
        $time_elapsed = time() - $time_start;
        $time_elapsed_pretty = '';
        $sec = $time_elapsed;
        if ($sec > 86400) {
            $day = floor($sec / 86400);
            $sec = $sec % 86400;

            $sec -= floor($sec % 60);
            if ($day > 3) {
                $sec -= floor($sec % 3600);
            }
            if ($day > 10) {
                $sec -= floor($sec % 86400);
            }

            $time_elapsed_pretty .= sprintf('%dd', $day);
        }
        if ($sec > 3600) {
            $hour = floor($sec / 3600);
            $sec = $sec % 3600;

            $time_elapsed_pretty .= sprintf('%dh', $hour);
        }
        if ($sec > 60) {
            $min = floor($sec / 60);
            $sec = $sec % 60;

            $time_elapsed_pretty .= sprintf('%dm', $min);
        }
        if ($sec > 0) {
            $time_elapsed_pretty .= sprintf('%ds', $sec);
        }

        $this->SetValue('Daemon_Running', $time_elapsed);
        $this->SetValue('Daemon_Running_Pretty', $time_elapsed_pretty);

        $this->SendDebug(__FUNCTION__, 'start=' . date('d.m.Y H:i:s', $time_start) . ', symcon running=' . $time_elapsed . 's (' . $time_elapsed_pretty . ')', 0);

        $sys = IPS_GetKernelPlatform();
        switch ($sys) {
            case 'SymBox':
                $res = $this->execute('ps -o comm,vsz,rss | grep symcon');
                if ($res == '' || count($res) < 1) {
                    $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($res, true), 0);
                    return false;
                }

                $r = preg_split("/[:\s]+/", $res[0]);
                if ($r == '' || count($r) < 3) {
                    $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($r, true), 0);
                    return false;
                }

                $col_size = $r[1];
                $col_rss = $r[2];

                if (preg_match('/^([0-9\.]*)[gG]/', $col_size, $x)) {
                    $mem_size = (int) ((float) $x[1] * 1024);
                } elseif (preg_match('/^([0-9\.]*)[mM]/', $col_size, $x)) {
                    $mem_size = (int) ((float) $x[1]);
                } else {
                    $mem_size = (int) ((float) $col_size / 1024);
                }
                if (preg_match('/^([0-9\.]*)[gG]/', $col_rss, $x)) {
                    $mem_rss = (int) ((float) $x[1] * 1024);
                } elseif (preg_match('/^([0-9\.]*)[mM]/', $col_rss, $x)) {
                    $mem_rss = (int) ((float) $x[1]);
                } else {
                    $mem_rss = (int) ((float) $col_size / 1024);
                }

                $this->SendDebug(__FUNCTION__, 'memory size=' . $mem_size . ', rss=' . $mem_rss, 0);

                $this->SetValue('Daemon_MemSize', $mem_size);
                $this->SetValue('Daemon_MemResident', $mem_rss);

                $res = $this->execute('top -b -n1 | grep "/usr/share/symcon/symcon service" | grep -v grep');
                if ($res == '' || count($res) < 1) {
                    $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($res, true), 0);
                    return false;
                }

                $r = preg_split("/[:\s]+/", $res[0]);
                if ($r == '' || count($r) < 9) {
                    $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($r, true), 0);
                    return false;
                }

                $col_pcpu = $r[6];

                $res = $this->execute('cat /proc/' . getmypid() . '/stat');
                if ($res == '' || count($res) < 1) {
                    $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($res, true), 0);
                    return false;
                }

                $r = preg_split("/[:\s]+/", $res[0]);
                if ($r == '' || count($r) < 52) {
                    $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($r, true), 0);
                    return false;
                }

                $CLK_TCK = 100;
                $utime = $r[13];
                $stime = $r[14];

                $cpu_used = $utime / $CLK_TCK + $stime / $CLK_TCK;
                $cpu_hourly = floor(($cpu_used / $time_elapsed) * 3600 * 10) / 10;
                $cpu_usage = (float) $col_pcpu;

                $this->SetValue('Daemon_CpuUsedTotal', $cpu_used);
                if ($time_elapsed > 3600) {
                    $this->SetValue('Daemon_CpuUsedHourly', $cpu_hourly);
                }
                $this->SetValue('Daemon_CpuUsage', $cpu_usage);

                $this->SendDebug(__FUNCTION__, 'cpu used=' . $cpu_used . 's, hourly=' . $cpu_hourly . 's/h, usage=' . $cpu_usage . '%', 0);
                break;
            case 'Docker':
            case 'Ubuntu (Docker)':
            case 'Raspberry Pi (Docker)':
                $res = $this->execute('cat /proc/' . getmypid() . '/stat');
                if ($res == '' || count($res) < 1) {
                    $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($res, true), 0);
                    return false;
                }

                $r = preg_split("/[:\s]+/", $res[0]);
                if ($r == '' || count($r) < 52) {
                    $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($r, true), 0);
                    return false;
                }

                $col_rss = $r[23];
                $col_vsize = $r[22];
                $col_size = $r[22];

                $mem_size = (int) ((float) $col_size / 1024);
                $mem_rss = (int) ((float) $col_rss / 1024);
                $mem_virt = (int) ((float) $col_vsize / 1024);

                $this->SendDebug(__FUNCTION__, 'memory size=' . $mem_size . ', rss=' . $mem_rss . ', virtual=' . $mem_virt, 0);

                $this->SetValue('Daemon_MemSize', $mem_size);
                $this->SetValue('Daemon_MemResident', $mem_rss);

                $CLK_TCK = 100;
                $utime = $r[13];
                $stime = $r[14];

                $cpu_used = $utime / $CLK_TCK + $stime / $CLK_TCK;
                $cpu_hourly = floor(($cpu_used / $time_elapsed) * 3600 * 10) / 10;

                $this->SetValue('Daemon_CpuUsedTotal', $cpu_used);
                if ($time_elapsed > 3600) {
                    $this->SetValue('Daemon_CpuUsedHourly', $cpu_hourly);
                }

                $this->SendDebug(__FUNCTION__, 'cpu used=' . $cpu_used . 's, hourly=' . $cpu_hourly . 's/h', 0);
                break;
            default:
                $res = $this->execute('ps -o size,rss,vsize,pmem,cputimes,pcpu -h -p' . getmypid());
                if ($res == '' || count($res) < 1) {
                    $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($res, true), 0);
                    return false;
                }

                $r = preg_split("/[:\s]+/", $res[0]);
                if ($r == '' || count($r) < 5) {
                    $this->SendDebug(__FUNCTION__, 'bad data: ' . print_r($r, true), 0);
                    return false;
                }

                $col_size = $r[0];
                $col_rss = $r[1];
                $col_vsize = $r[2];
                $col_pmem = $r[3];
                $col_cputimes = $r[4];
                $col_pcpu = $r[5];

                $mem_size = (int) ((float) $col_size / 1024);
                $mem_rss = (int) ((float) $col_rss / 1024);
                $mem_virt = (int) ((float) $col_vsize / 1024);
                $mem_load = (int) ((float) $col_pmem / 1024);

                $this->SendDebug(__FUNCTION__, 'memory size=' . $mem_size . ', rss=' . $mem_rss . ', virtual=' . $mem_virt, 0);

                $this->SetValue('Daemon_MemSize', $mem_size);
                $this->SetValue('Daemon_MemResident', $mem_rss);

                $cpu_used = $col_cputimes;
                $cpu_hourly = floor(($cpu_used / $time_elapsed) * 3600 * 10) / 10;
                $cpu_usage = (float) $col_pcpu;

                $this->SetValue('Daemon_CpuUsedTotal', $cpu_used);
                if ($time_elapsed > 3600) {
                    $this->SetValue('Daemon_CpuUsedHourly', $cpu_hourly);
                }
                $this->SetValue('Daemon_CpuUsage', $cpu_usage);

                $this->SendDebug(__FUNCTION__, 'cpu used=' . $cpu_used . 's, hourly=' . $cpu_hourly . 's/h, usage=' . $cpu_usage . '%', 0); break;
        }

        return true;
    }
}
