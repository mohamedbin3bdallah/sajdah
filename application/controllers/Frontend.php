<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Frontend extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */

	function __construct()
    {
		parent::__construct();
		$this->sections = array();
		$sections = $this->Admin_mo->getwhere('sections',array('scactive'=>'1'));
		if(!empty($sections))
		{
			foreach($sections as $section) { $this->sections[$section->scid] = $section->sccode; }
		}
		
		$this->pages = array();
		if(in_array('PG',$this->sections))
		{
			$pages = $this->Admin_mo->getwhere('pages',array('pgactive'=>'1'));
			if(!empty($sections))
			{
				foreach($pages as $page) { $this->pages['url'][$page->pgid] = $page->pgurl; $this->pages['title'][$page->pgid] = $page->pgtitle; $this->pages['desc'][$page->pgid] = $page->pgdesc; $this->pages['keywords'][$page->pgid] = $page->pgkeywords; }
			}
		}
	}

	public function errorpage()
	{
		$this->load->view('404');
	}

	public function index()
	{
		if(!empty($this->pages) && in_array('index',$this->pages['url']))
		{
			$data['admessage'] = '';
			$data['pageid'] = array_search('index', $this->pages['url']);
			$data['system'] = $this->Admin_mo->getrow('system',array('id'=>'1'));
			$data['contact'] = $this->Admin_mo->getrow('contact',array('ctid'=>'1'));
			$data['slides'] = $this->Admin_mo->getwhere('slides',array('sdactive'=>'1'));
			$data['abouts'] = $this->Admin_mo->getwhere('about',array('abactive'=>'1'));
			$data['categories'] = $this->Admin_mo->getwhere('categories',array('cgactive'=>'1'));
			$data['products'] = $this->Admin_mo->getwhere('products',array('practive'=>'1'));
			if($this->config->item('products_thumb_folder') != '')
			{
				$data['products_thumb_folder'] = $this->config->item('products_thumb_folder');
				$data['products_folder'] = $this->config->item('products_folder');
			}
			else $data['products_folder'] = $data['products_thumb_folder'] = $this->config->item('products_folder');
			$data['slides_thumb_folder'] = $this->config->item('slides_thumb_folder');
			$data['slides_folder'] = $this->config->item('slides_folder');
			$this->load->view('calenderdate');
			$this->load->view('frontend/index',$data);
		}
		else redirect('404', 'refresh');
	}

	public function sendmessage()
	{
		if(in_array('CT',$this->sections) && ($this->session->userdata('uid') == FALSE || !$this->input->cookie('uid', TRUE)))
		{
		$this->form_validation->set_error_delimiters('<div class="alert alert-danger alert-dismissible fade in" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>', '</div>');
		$this->form_validation->set_rules('name', 'الاسم' , 'trim|required|max_length[255]');
		$this->form_validation->set_rules('email', 'البريد الاكتروني' , 'trim|required|max_length[255]|valid_email');
		$this->form_validation->set_rules('title', 'العنوان' , 'trim|required|max_length[255]');
		$this->form_validation->set_rules('body', 'الرسالة' , 'trim|required');
		if($this->form_validation->run() == FALSE)
		{
			$data['admessage'] = '';
			$data['pageid'] = array_search('index', $this->pages['url']);
			$data['system'] = $this->Admin_mo->getrow('system',array('id'=>'1'));
			$data['contact'] = $this->Admin_mo->getrow('contact',array('ctid'=>'1'));
			$data['slides'] = $this->Admin_mo->getwhere('slides',array('sdactive'=>'1'));
			$data['abouts'] = $this->Admin_mo->getwhere('about',array('abactive'=>'1'));
			$data['categories'] = $this->Admin_mo->getwhere('categories',array('cgactive'=>'1'));
			$data['products'] = $this->Admin_mo->getwhere('products',array('practive'=>'1'));
			if($this->config->item('products_thumb_folder') != '')
			{
				$data['products_thumb_folder'] = $this->config->item('products_thumb_folder');
				$data['products_folder'] = $this->config->item('products_folder');
			}
			else $data['products_folder'] = $data['products_thumb_folder'] = $this->config->item('products_folder');
			$data['slides_thumb_folder'] = $this->config->item('slides_thumb_folder');
			$data['slides_folder'] = $this->config->item('slides_folder');
			$this->load->view('calenderdate');
			$this->load->view('frontend/index',$data);
		}
		else
		{
			$this->load->library('notifications');
			$set_arr = array('mgeid'=>0, 'mgname'=>set_value('name'), 'mgemail'=>set_value('email'), 'mgtitle'=>set_value('title'), 'mgbody'=>set_value('body'), 'mgtime'=>time());
			$mgid = $this->Admin_mo->set('messages', $set_arr);
			if(empty($mgid))
			{
				$_SESSION['time'] = time(); $_SESSION['message'] = 'somthingwrong';
				redirect('index', 'refresh');
			}
			else
			{
			$this->notifications->addNotify(0,'CT',' اضاف رسالة '.set_value('title'),'inner join usertypes on users.uutid = usertypes.utid where usertypes.utprivileges like "%,ctsee,%" or usertypes.utprivileges like "%,ctedit,%"');
				$_SESSION['time'] = time(); $_SESSION['message'] = 'success';
				redirect('index', 'refresh');
			}
		}
		}
		else
		{
			redirect('index', 'refresh');
		}
	}

	public function sendemail($email,$subject,$body)
	{
		require_once('../PHPMailer/class.phpmailer.php');
		require_once('../PHPMailer/class.smtp.php');
		require_once('../PHPMailer/PHPMailerAutoload.php');
		$mail             = new PHPMailer(); // defaults to using php "mail()"
		$mail->IsSMTP(); // telling the class to use SMTP
		//$mail->Host       = "smtp.secureserver.net";
		$mail->Host       = "localhost";
		//	$mail->Host       = "smtpout.secureserver.net";      // sets GMAIL as the SMTP server
		//	$mail->SMTPAuth   = true;                  // enable SMTP authentication
		//	$mail->SMTPSecure = 'ssl';
		//	$mail->Port = 465;
		//$mail->SMTPDebug  = 2;                     // enables SMTP debug information (for testing)
		//$mail->Username   = "";  // GMAIL username
		//$mail->Password   = "";					
		//$mail->AddReplyTo("name@yourdomain.com","First Last");
		$address = "info@sajdah.com";
		$mail->SetFrom($address, 'INFO');
		$mail->AddAddress($email);
		$mail->Subject    = $subject;
		//$mail->AltBody    = "You can active your account on : "; // optional, comment out and test
		$mail->Body    = $body;
		if($mail->Send()) return 1;
		else return 0;
	}
}