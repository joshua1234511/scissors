<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * SMS library
 *
 * Library with utilities to send texts via SMS Gateway (requires proxy implementation)
 */

class Sms_lib
{
	private $CI;

  	public function __construct()
	{
		$this->CI =& get_instance();
    //$this->load->library('Textlocal');
	}

	/*
	 * SMS sending function
	 * Example of use: $response = sendSMS('4477777777', 'My test message');
	 */
	public function sendSMS($phone, $message)
	{
    require 'textlocal.class.php';
		$username   = $this->CI->config->item('msg_uid');
		$password   = $this->CI->encryption->decrypt($this->CI->config->item('msg_pwd'));
		$originator = $this->CI->config->item('msg_src');

		$response = FALSE;

		// if any of the parameters is empty return with a FALSE
		if(empty($username) || empty($password) || empty($phone) || empty($message) || empty($originator))
		{
			$response = FALSE;
		}
		else
		{
			$response = TRUE;

			// make sure passed string is url encoded
			$message = rawurlencode($message);

      $textlocal = new Textlocal($username, '',$password);

      $numbers = array($phone);
      $sender = $originator;

      try {
          $result_sms = $textlocal->sendSms($numbers, $message);
          //$result_sms = new stdClass();
          //$result_sms->status = 'success';
          if(isset($result_sms->status) && $result_sms->status == 'success'){
            $response = $result_sms;
          }
      } catch (Exception $e) {
          $response = FALSE;
      }
		}

		return $response;
	}
}

?>
