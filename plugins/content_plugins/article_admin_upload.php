<?
	chdir($_SERVER['DOCUMENT_ROOT']);
	
	require_once 'config.php';
	require_once 'config_terms.php';
	require_once 'includes/logic.php';
	require_once 'includes/sessionhandler.php';
	
	session_start();
	
	if (!logic_is_admin()) 
	{
		header("location: /");
	}
	$aid = $_REQUEST['aid'];
//	print_r($_FILES);
//	print_r($_REQUEST);
	if ($aid>0 && isset($_FILES['file']))
	{
		
		logic_upload_article_file($aid, $_FILES['file']);
	}
	
	if (isset($_REQUEST['delete']))
	{
		logic_delete_article_file($_REQUEST['delete']);
	}
?>
<? if ($aid!=-1) { ?>
<form action=article_admin_upload.php method=post enctype="multipart/form-data">
<input type=hidden name=aid value=<?=$aid?>>
<input type=file name=file><input type=submit value=Upload class="btn">
</form>
<? } ?>
<?
	$files = logic_get_article_files($aid,false);
	for ($i=0;$i<sizeof($files);$i++)
	{
		echo "
		<li>
		<a target=_blank href=http://rtd.dk/uploads/article_file/?afid={$files[$i]['afid']}>
		{$files[$i]['filename']}
		</a> 
		- <a href=article_admin_upload.php?aid={$aid}&delete={$files[$i]['afid']}>Slet</a>
		";
	}
?>