<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Profile extends CI_Controller
{	
	public function __construct()
	{
		parent::__construct('employees');
	}

	public function index()
	{	
		$this->load->model('Employee');
	    $model = $this->Employee;
	    $logged_in_employee_info = $model->get_logged_in_employee_info();
	    $data['allowed_modules'] = $this->Module->get_allowed_modules($logged_in_employee_info->person_id);
		$data['user_info'] = $logged_in_employee_info;
		$data['controller_name'] = 'profile';
		$data['package_name'] = "No Package";
		$data['points'] = 0;
		$package_id = $this->Customer->get_info($logged_in_employee_info->person_id)->package_id;
				if(!empty($package_id))
				{
					$data['package_name'] = $this->Customer_rewards->get_name($package_id);
					$points = $this->Customer->get_info($logged_in_employee_info->person_id)->points;
					$data['points'] = ($points==NULL ? 0 : $points);
				}
		$data['specific_input_name'] = $this->lang->line('reports_customer');

		$customers = array();
		$customers[$logged_in_employee_info->person_id] = $this->xss_clean($logged_in_employee_info->first_name . ' ' . $logged_in_employee_info->last_name);
		$data['specific_input_data'] = $customers;
		$this->load->vars($data);
		$this->load->view('profile');
	}

	/*
	Loads the change password form
	*/
	public function change_password($employee_id = -1)
	{	
		$this->load->model('Employee');
		$model = $this->Employee;
		$person_info = $model->get_info($employee_id);
		foreach(get_object_vars($person_info) as $property => $value)
		{
			$person_info->$property = $this->xss_clean($value);
		}
		$data['person_info'] = $person_info;

		$this->load->view('employees/form_change_password', $data);
	}

	/*
	* Internal method to do XSS clean in the derived classes
	*/
	protected function xss_clean($str, $is_image = FALSE)
	{
		// This setting is configurable in application/config/config.php.
		// Users can disable the XSS clean for performance reasons
		// (cases like intranet installation with no Internet access)
		if($this->config->item('ospos_xss_clean') == FALSE)
		{
			return $str;
		}
		else
		{
			return $this->security->xss_clean($str, $is_image);
		}
	}

	public function specific_customer_input()
	{	
		$this->load->model('Employee');
	    $model = $this->Employee;
	    $logged_in_employee_info = $model->get_logged_in_employee_info();
	    $data['allowed_modules'] = $this->Module->get_allowed_modules($logged_in_employee_info->person_id);
		$data['user_info'] = $logged_in_employee_info;
		$data['controller_name'] = 'profile';
		$data['package_name'] = "No Package";
		$data['points'] = 0;
		$package_id = $this->Customer->get_info($logged_in_employee_info->person_id)->package_id;
				if(!empty($package_id))
				{
					$data['package_name'] = $this->Customer_rewards->get_name($package_id);
					$points = $this->Customer->get_info($logged_in_employee_info->person_id)->points;
					$data['points'] = ($points==NULL ? 0 : $points);
				}

		$this->load->vars($data);
		$data['specific_input_name'] = $this->lang->line('reports_customer');

		$customers = array();
		$customers[$logged_in_employee_info->person_id] = $this->xss_clean($logged_in_employee_info->first_name . ' ' . $logged_in_employee_info->last_name);
		$data['specific_input_data'] = $customers;

		$this->load->view('reports/specific_input', $data);
	}

	public function specific_customer($start_date, $end_date, $customer_id, $sale_type)
	{
		$inputs = array('start_date' => $start_date, 'end_date' => $end_date, 'customer_id' => $customer_id, 'sale_type' => $sale_type);
		$this->load->model('Employee');
	    $model = $this->Employee;
	    $logged_in_employee_info = $model->get_logged_in_employee_info();
	    if($logged_in_employee_info->person_id != $customer_id)
		{
			redirect('profile');
		}
	    $data['allowed_modules'] = $this->Module->get_allowed_modules($logged_in_employee_info->person_id);
		$data['user_info'] = $logged_in_employee_info;
		$data['controller_name'] = 'profile';
		$data['package_name'] = "No Package";
		$data['points'] = 0;
		$package_id = $this->Customer->get_info($logged_in_employee_info->person_id)->package_id;
				if(!empty($package_id))
				{
					$data['package_name'] = $this->Customer_rewards->get_name($package_id);
					$points = $this->Customer->get_info($logged_in_employee_info->person_id)->points;
					$data['points'] = ($points==NULL ? 0 : $points);
				}

		$this->load->vars($data);
		$this->load->model('reports/Specific_customer');
		$model = $this->Specific_customer;

		$model->create($inputs);

		$headers = $this->xss_clean($model->getDataColumnsCustomer());
		$report_data = $model->getData($inputs);

		$summary_data = array();
		$details_data = array();
		$details_data_rewards = array();

		foreach($report_data['summary'] as $key => $row)
		{
			$summary_data[] = $this->xss_clean(array(
				'id' => anchor('sales/receipt/'.$row['sale_id'], 'POS '.$row['sale_id'], array('target'=>'_blank')),
				'sale_date' => $row['sale_date'],
				'quantity' => to_quantity_decimals($row['items_purchased']),
				'employee_name' => $row['employee_name'],
				'subtotal' => to_currency($row['subtotal']),
				'tax' => to_currency_tax($row['tax']),
				'total' => to_currency($row['total']),
				'payment_type' => $row['payment_type'],
				'comment' => $row['comment']));

			foreach($report_data['details'][$key] as $drow)
			{
				$details_data[$row['sale_id']][] = $this->xss_clean(array(
					$drow['name'],
					$drow['category'],
					$drow['serialnumber'],
					$drow['description'],
					to_quantity_decimals($drow['quantity_purchased']),
					to_currency($drow['subtotal']),
					to_currency_tax($drow['tax']),
					to_currency($drow['total']),
					$drow['discount_percent'].'%'));
			}

			if(isset($report_data['rewards'][$key]))
			{
				foreach($report_data['rewards'][$key] as $drow)
				{
					$details_data_rewards[$row['sale_id']][] = $this->xss_clean(array($drow['used'], $drow['earned']));
				}
			}
		}

		$customer_info = $this->Customer->get_info($customer_id);
		$data = array(
			'title' => $this->xss_clean($customer_info->first_name . ' ' . $customer_info->last_name . ' ' . $this->lang->line('reports_report')),
			'subtitle' => $this->_get_subtitle_report(array('start_date' => $start_date, 'end_date' => $end_date)),
			'headers' => $headers,
			'summary_data' => $summary_data,
			'details_data' => $details_data,
			'details_data_rewards' => $details_data_rewards,
			'overall_summary_data' => $this->xss_clean($model->getSummaryDataCustomer($inputs))
		);

		$this->load->view('reports/tabular_details_customer', $data);
	}

	//	Returns subtitle for the reports
	private function _get_subtitle_report($inputs)
	{
		$subtitle = '';

		if(empty($this->config->item('date_or_time_format')))
		{
			$subtitle .= date($this->config->item('dateformat'), strtotime($inputs['start_date'])) . ' - ' .date($this->config->item('dateformat'), strtotime($inputs['end_date']));
		}
		else
		{
			$subtitle .= date($this->config->item('dateformat').' '.$this->config->item('timeformat'), strtotime(rawurldecode($inputs['start_date']))) . ' - ' . date($this->config->item('dateformat').' '.$this->config->item('timeformat'), strtotime(rawurldecode($inputs['end_date'])));
		}
		
		return $subtitle;
	}
}
?>
