<?
/*--- RTD DEVELOPMENT ---
	configuration options
	
	26-10-2012	rasmus@3kings.dk 	draft
	31-10-2012	rasmus@3kings.dk	added locale
	06-11-2012	rasmus@3kings.dk	meeting images path
	27-11-2012	rasmus@3kings.dk	uploaded user image path (profile images)
	28-11-2012	rasmus@3kings.dk	changed ADMIN role to 18 instead of 22 (old WEB)
	03-01-2013  rasmus@3kings.dk  	MEETING_FILES_UPLOAD_PATH
	02-03-2013  rasmsu@3kings.dk  	mail pause flag added
	13-05-2013	rasmus@3kings.dk	path differentiation for cronjob/non-cronjob
*/

	if (!defined("CONFIG_LOADED"))
	{
		define("DISABLE_MAIL_SENDING", true);		
define('FAVICO_URL', '/img/favicon/rtd.ico');
		define("UNITTEST", FALSE);
		define("NEWBOARD_SUBMISSION_PERIOD_START", 4); // starting first of april
		define("NEWBOARD_SUBMISSION_PERIOD_END", 6); // ending 30th of june
		

		define("CLUB_JUBILEES_YEAR", "10,20,25,30,40,50,60,70,75,80,90,100");
		define("MEMBER_JUBILEES_YEAR", "10,15,20");
		
		define("LANDING_PAGE_PUBLIC", 1);
		define("LANDING_PAGE_PRIVATE", 1);
		//define("LANDING_PAGE_PRIVATE", 26);

		//// db connection
		define("DATABASE_NAME","test_wp");
		define("DATABASE_HOST", "localhost");
		define("DATABASE_USER", "rtd_dk"); 
		define("DATABASE_PASSWORD", "ryed3VB2VfRdeLpK"); 



		/// debug info
		error_reporting(E_ALL);
		ini_set('display_errors', '1');
		ini_set('post_max_size', '64M'); ini_set('upload_max_filesize', '64M');		
		/// admin mail
		define("ADMIN_MAIL", "web@rtd.dk");
		define("SMTP_SERVER", "smtp.wannafind.dk");
		//define("SMTP_SERVER", "localhost");
		define("COOKIE_SALT", "salatKANYLEgu1tar");
		
		/// this role is the minimum required to be able to logon to site
		define("MINIMUM_ROLE_ALLOWED_RID", 6);
		define("USER_LEAVE_ROLE_RID", 33);

		// default password for new users
		define("DEFAULT_NEW_USER_PASSWORD", "kode123");
		
		/// admin role
		define("ADMIN_ROLE_RID", 18);
		
		//// loop protection
		define("CONFIG_LOADED", "Yes");
		
		define("HONORARY_RID", 26);

		 if (PHP_SAPI == 'cli')
		  {
			$path = "/var/www/vhosts/rtd.dk/dev/";
				/// used for meeting images uploads
			define("MAIL_ATTACHMENT_UPLOAD_PATH", $path."/uploads/mail_attachment/");
			define("BANNER_UPLOAD_PATH", $path."/uploads/banners/");
			define("MEETING_IMAGES_UPLOAD_PATH", $path."/uploads/meeting_image/");
			define("MEETING_FILES_UPLOAD_PATH", $path."/uploads/meeting_file/");
			define("CLUB_LOGO_PATH", $path."/uploads/club_logos/");
			define("MOBILE_DOWNLOAD_PATH", $path.'/uploads/mobile_files/');
			define("USER_IMAGES_UPLOAD_PATH", $path."/uploads/user_image/");
			define("ARTICLE_FILE_UPLOAD_PATH", $path."/uploads/article_file/");
		  }
		  else
		  {
				/// used for meeting images uploads
			define("MAIL_ATTACHMENT_UPLOAD_PATH", $_SERVER['DOCUMENT_ROOT']."/uploads/mail_attachment/");
				define("BANNER_UPLOAD_PATH", $_SERVER['DOCUMENT_ROOT']."/uploads/banners/");
				define("MEETING_IMAGES_UPLOAD_PATH", $_SERVER['DOCUMENT_ROOT']."/uploads/meeting_image/");
			define("MEETING_FILES_UPLOAD_PATH", $_SERVER['DOCUMENT_ROOT']."/uploads/meeting_file/");
			define("CLUB_LOGO_PATH", $_SERVER['DOCUMENT_ROOT']."/uploads/club_logos/");
			define("MOBILE_DOWNLOAD_PATH", $_SERVER['DOCUMENT_ROOT'].'/uploads/mobile_files/');
			define("USER_IMAGES_UPLOAD_PATH", $_SERVER['DOCUMENT_ROOT']."/uploads/user_image/");
			
			define("ARTICLE_FILE_UPLOAD_PATH", $_SERVER['DOCUMENT_ROOT']."/uploads/article_file/");
		  }

	define("MOBILE_DOWNLOAD_PATH_WEB", '/uploads/mobile_files/');
		
	
		

		define("NATIONAL_PRESIDENT_MAIL", "lf@rtd.dk");
    define("NATIONAL_SECRETARY_MAIL", "ls@rtd.dk");	
    define("NATIONAL_VICE_PRESIDENT_MAIL", "vlf@rtd.dk");
		define("CLUB_BOARD_ROLES", "('F','S','I','N','IRO','K')");
		define("NATIONAL_BOARD_ROLES", "('NIRO','VLF','LF','LS','LK','WEB','SHOP','DF','ALF','LA','RED')");
		define("DISTRICT_CHAIRMAN_SHORT", "DF");
		define("HONORARY_ROLE_RID", 26); // ÆM
		define("SECRETARY_ROLE_RID", 10); // S
		define("CHAIRMAN_ROLE_RID", 9); // F
		define("CHAIRMAN_SHORT_NAME", "F");
		define("MEMBER_ROLE_RID", 6); // M

		define("NB_MAIL_POSTFIX", "@rtd.dk"); // National board mails are sent as <role>@rtd.dk, e.g. df2@rtd.dk or lf@rtd.dk
		
		
		/// import protection
		define("IMPORT_PASSWORD", "skibPapirz4ks");
		
		/// web definitions
		define("RT_LOCALE", "da_DK");
		define("RT_TEMPLATE", "./template/simple.html");
		define("RT_TEMPLATE_PRINT", "./template/print.html");
    define("RT_TEMPLATE_MOBILE", $_SERVER['DOCUMENT_ROOT']."/template/mobile.html");
		
		// sender of mass mails		
		define("MASS_MAILER_REPLY_TO", "Round Table Danmark <noreply@rtd.dk>");
    define("MASS_MAILER_REPLY_MAIL", "noreply@rtd.dk");
    define("MASS_MAILER_REPLY_WHO", "Round Table Danmark");
		
		// cron job password
		define("CRONJOB_PWD", "k4rk1ud");
		
		define("IMAGE_MISSING_PROFILE", $_SERVER['DOCUMENT_ROOT'].'/template/images/missing_profile_photo.png');
    
    
    define("OLD_FTP_SERVER", "localhost");
    define("OLD_FTP_USER", "old_site_user");
    define("OLD_FTP_PASSWORD","kaffeB4R1");
	}?>