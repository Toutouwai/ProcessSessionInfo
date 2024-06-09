<?php
/*
 * Session class by Frits dot vanCampen at moxio dot com
 * https://www.php.net/manual/en/function.session-decode.php#108037
 *
 * This class relies on the "feature" that unserialize() ignores all further input when
 * it thinks it's done as the only practical way to work out where the boundaries
 * are within the raw session string.
 *
 * PHP >= 8.3 will give error notices when the string given to unserialize() has trailing bytes
 * but seeing as these trailing bytes are expected here by design we are suppressing the
 * notices with the @ operator before unserialize().
 */
class Session {
	public static function unserialize($session_data) {
		$method = ini_get("session.serialize_handler");
		switch ($method) {
			case "php":
				return self::unserialize_php($session_data);
				break;
			case "php_binary":
				return self::unserialize_phpbinary($session_data);
				break;
			default:
				throw new Exception("Unsupported session.serialize_handler: " . $method . ". Supported: php, php_binary");
		}
	}

	private static function unserialize_php($session_data) {
		$return_data = array();
		$offset = 0;
		while ($offset < strlen($session_data)) {
			if (!strstr(substr($session_data, $offset), "|")) {
				throw new Exception("invalid data, remaining: " . substr($session_data, $offset));
			}
			$pos = strpos($session_data, "|", $offset);
			$num = $pos - $offset;
			$varname = substr($session_data, $offset, $num);
			$offset += $num + 1;
			// Suppress warning notice that occurs in PHP >= 8.3
			$data = @unserialize(substr($session_data, $offset));
			$return_data[$varname] = $data;
			$offset += strlen(serialize($data));
		}
		return $return_data;
	}

	private static function unserialize_phpbinary($session_data) {
		$return_data = array();
		$offset = 0;
		while ($offset < strlen($session_data)) {
			$num = ord($session_data[$offset]);
			$offset += 1;
			$varname = substr($session_data, $offset, $num);
			$offset += $num;
			// Suppress warning notice that occurs in PHP >= 8.3
			$data = @unserialize(substr($session_data, $offset));
			$return_data[$varname] = $data;
			$offset += strlen(serialize($data));
		}
		return $return_data;
	}
}
