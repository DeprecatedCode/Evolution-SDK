<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
        <title>(e) System Fault</title>
		<style type="text/css">
			body { color:#3d484f; font-size:13px; padding: 0px; margin: 40px; font-family: 'Lucida Grande', 'Arial';width:600px; }
			p { line-height: 160%;}
			code {color:#ff8400;}
			#branding { background:#FFF url('/_evolution/visual/static/logo.png') no-repeat left center; padding-left: 65px;}
			.fault { -moz-border-radius-topright: 10px;-moz-border-radius-bottomright: 10px; border-bottom-right-radius:10px;border-top-right-radius: 10px; background-color:#c50000; margin: 20px 0px 20px -40px; padding: 15px 20px 15px 40px; color:white; font-weight:bold;font-size:16px; margin-bottom: 30px;}
			.doc { -moz-border-radius-topright: 10px;-moz-border-radius-bottomright: 10px; border-bottom-right-radius:10px;border-top-right-radius: 10px; background-color:#006ea7; margin: 50px 0px 20px -40px; padding: 15px 20px 15px 40px; color:white; font-weight:bold;font-size:16px; margin-bottom: 30px;}
			
			h2 {
				font-size: 18px;
				color:#ff7600;
				margin: 30px 0px 10px 0px;
				padding-bottom: 4px;
				border-bottom: 3px solid #d4d4d4;
			}

			h3 {
				font-size: 14px;
				margin: 20px 0px 10px 0px;
				padding-bottom: 4px;
				border-bottom: 2px solid #53626c;
				width: 70%;
			}
		</style>
    </head>
    <body id="general">
		<h1 id="branding"><span style="color:#d6d6d6">comin' in hot from</span> (e)volution</h1>
		<div class="fault"><?php echo $event_text; ?></div>
		<?php echo $message;?><pre><?php debug_print_backtrace();?></pre>
		<?php if($doc):?>
		<div class="doc">Additional Documentation</div>
		<?php echo $doc; ?>
		<?php endif;?>
    </body>
</html>