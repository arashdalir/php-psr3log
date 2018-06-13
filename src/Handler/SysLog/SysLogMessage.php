<?php
/**
 * Created by PhpStorm.
 * User: ada
 * Date: 12-Jun-18
 * Time: 11:33
 */

namespace ArashDalir\Handler\SysLog;

use ArashDalir\Foundation\LogMessage;
use Psr\Log\LogLevel;

class SysLogMessage extends LogMessage{
	const SYSLOG_EMERG = 0;
	const SYSLOG_ALERT = 1;
	const SYSLOG_CRIT = 2;
	const SYSLOG_ERR = 3;
	const SYSLOG_WARNING = 4;
	const SYSLOG_NOTICE = 5;
	const SYSLOG_INFO = 6;
	const SYSLOG_DEBUG = 7;

	const VERSION_0 = 0;
	const VERSION_1 = 1;

	protected $structured_data;
	protected $host_name;
	protected $facility;
	protected $os_conform = true;
	protected $version;

	/** @var null - value is automatically generated by asString; definition as helper for get_object_vars() function */
	protected $facility_level = null;
	protected $timestamp_syslog_v0 = null;
	protected $timestamp_syslog_v1 = null;

	static private $os_log_levels = array(
		LogLevel::EMERGENCY => LOG_EMERG,
		LogLevel::ALERT => LOG_ALERT,
		LogLevel::CRITICAL => LOG_CRIT,
		LogLevel::ERROR => LOG_ERR,
		LogLevel::WARNING => LOG_WARNING,
		LogLevel::NOTICE => LOG_NOTICE,
		LogLevel::INFO => LOG_INFO,
		LogLevel::DEBUG => LOG_DEBUG,
	);

	static private $syslog_levels = array(
		LogLevel::EMERGENCY => self::SYSLOG_EMERG,
		LogLevel::ALERT => self::SYSLOG_ALERT,
		LogLevel::CRITICAL => self::SYSLOG_CRIT,
		LogLevel::ERROR => self::SYSLOG_ERR,
		LogLevel::WARNING => self::SYSLOG_WARNING,
		LogLevel::NOTICE => self::SYSLOG_NOTICE,
		LogLevel::INFO => self::SYSLOG_INFO,
		LogLevel::DEBUG => self::SYSLOG_DEBUG,
	);

	function __construct($version = self::VERSION_1)
	{
		parent::__construct();

		$this->setVersion($version);
	}

	/**
	 * @return mixed
	 */
	public function getStructuredData()
	{
		return $this->structured_data;
	}

	/**
	 * @param mixed $structured_data
	 *
	 * @return SysLogMessage
	 */
	public function setStructuredData($structured_data)
	{
		$this->structured_data = $structured_data;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * @param int $version
	 *
	 * @return SysLogMessage
	 */
	public function setVersion($version)
	{
		switch($version)
		{
		case self::VERSION_0:
			$this->format = SysLogFormats::FORMAT_V0;
			break;

		case self::VERSION_1:
			$this->format = SysLogFormats::FORMAT_V1;
			break;
		}
		$this->version = $version;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getHostName()
	{
		return $this->host_name;
	}

	/**
	 * @param mixed $host_name
	 *
	 * @return SysLogMessage
	 */
	public function setHostName($host_name)
	{
		$this->host_name = $host_name;
		return $this;
	}

	/**
	 * @param bool $normalize
	 *
	 * @return mixed
	 */
	public function getFacility($normalize = false)
	{
		$facility = $this->facility;

		if ($normalize)
		{
			$facility = (int)($facility/8);
		}
		return $facility;
	}

	/**
	 * @param mixed $facility
	 *
	 * @param bool  $os_conform
	 *
	 * @return SysLogMessage
	 * @throws Exception\InvalidFacilityException
	 */
	public function setFacility($facility, $os_conform = true)
	{
		if($facility < 8)
		{
			$facility = 0;
		}

		Facilities::isFacilityValid($facility, $os_conform);

		$this->os_conform = $os_conform;
		$this->facility = $facility;
		return $this;
	}

	function __toString()
	{
		return $this->format($this->format);
	}

	function asString($property)
	{
		$value = parent::asString($property);

		switch($property)
		{
		case "timestamp_syslog_v0":
			$timestamp = $this->getTimestamp();
			$day = date("j", $timestamp);
			$month = date("M", $timestamp);
			$time = date("H:i:s");

			if (strlen($day) == 1)
			{
				$day = " {$day}";
			}
			$value = "{$month} {$day} {$time}";
			break;

		case "timestamp":
			$timestamp = $this->getTimestamp();
			$value = date("c", $timestamp);
			break;

		case "facility_level":
			$value = $this->facility|$this->asString("level");
			break;

		case "level":
			if(!$this->os_conform)
			{
				$levels = static::$syslog_levels;
			}
			else
			{
				$levels = static::$os_log_levels;
			}

			$value = $levels[$this->level];
			break;

		case "context":
			if ($value)
			{
				$value = "|". $value;
			}
			break;

		case "structured_data":
			break;
		}

		if ($this->version == self::VERSION_1)
		{
			$nullables = array("host_name", "app_name", "process_id", "message_id", "timestamp", "structured_data", );

			if (in_array($property, $nullables) && !$value)
			{
				$value = "-";
			}
		}

		return $value;
	}
}