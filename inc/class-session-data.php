<?php

namespace Pantheon_Sessions;

/**
 * Database table fields in PHP for static analysis.
 */
class Session_Data {

	/**
	 * @var int
	 */
	public $user_id;

	/**
	 * @var string
	 */
	public $session_id;

	/**
	 * @var string
	 */
	public $secure_session_id;

	/**
	 * @var string
	 */
	public $ip_address;

	/**
	 * @var string
	 */
	public $datetime;

	/**
	 * @var string
	 */
	public $data;

}
