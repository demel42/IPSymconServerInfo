<?php

declare(strict_types=1);

trait ServerInfoLocalLib
{
    private function GetFormStatus()
    {
        $formStatus = $this->GetCommonFormStatus();

        return $formStatus;
    }

    public static $STATUS_INVALID = 0;
    public static $STATUS_VALID = 1;
    public static $STATUS_RETRYABLE = 2;

    private function CheckStatus()
    {
        switch ($this->GetStatus()) {
            case IS_ACTIVE:
                $class = self::$STATUS_VALID;
                break;
            default:
                $class = self::$STATUS_INVALID;
                break;
        }

        return $class;
    }

	public function InstallVarProfiles(bool $reInstall = false)
    {
        if ($reInstall) {
            $this->SendDebug(__FUNCTION__, 'reInstall=' . $this->bool2str($reInstall), 0);
        }

        $this->CreateVarProfile('ServerInfo.Duration', VARIABLETYPE_INTEGER, ' sec', 0, 0, 0, 0, '', [], $reInstall);
        $this->CreateVarProfile('ServerInfo.Frequency', VARIABLETYPE_INTEGER, ' MHz', 0, 0, 0, 0, '', [], $reInstall);

        $this->CreateVarProfile('ServerInfo.GB', VARIABLETYPE_FLOAT, ' GB', 0, 0, 0, 0, '', [], $reInstall);
        $this->CreateVarProfile('ServerInfo.Load', VARIABLETYPE_FLOAT, '', 0, 0, 0, 2, '', [], $reInstall);
        $this->CreateVarProfile('ServerInfo.MB', VARIABLETYPE_FLOAT, ' MB', 0, 0, 0, 0, '', [], $reInstall);
        $this->CreateVarProfile('ServerInfo.Temperature', VARIABLETYPE_FLOAT, ' °C', 0, 0, 0, 0, '', [], $reInstall);
        $this->CreateVarProfile('ServerInfo.Usage', VARIABLETYPE_FLOAT, ' %', 0, 0, 0, 1, '', [], $reInstall);
    }
}
