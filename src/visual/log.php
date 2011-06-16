<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
        <title>(e) Script Analysis</title>
		<style type="text/css">
			body { color:#3d484f; font-size:13px; padding: 0px; margin: 40px; font-family: 'Lucida Grande', 'Arial';width:600px; }
			p { line-height: 160%;}
			#branding { background:#FFF url('/_evolution/visual/static/logo.png') no-repeat left center; padding-left: 65px;}
			.fault { -moz-border-radius-topright: 10px;-moz-border-radius-bottomright: 10px; border-bottom-right-radius:10px;border-top-right-radius: 10px; background-color:#c50000; margin: 20px 0px 20px -40px; padding: 15px 20px 15px 40px; color:white; font-weight:bold;font-size:16px; margin-bottom: 30px;}
			.doc { -moz-border-radius-topright: 10px;-moz-border-radius-bottomright: 10px; border-bottom-right-radius:10px;border-top-right-radius: 10px; background-color:#006ea7; margin: 50px 0px 20px -40px; padding: 15px 20px 15px 40px; color:white; font-weight:bold;font-size:16px; margin-bottom: 30px;}
			ul li {
				list-style-type:none;
				font-size: 11px;
				margin-bottom: 16px;
			}
			ul {
				padding-left:0px;
			}
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
			.label {
				border-radius:4px;
				padding: 3px 5px;
				font-weight:bold;
				font-size:10px;
				background:orange;
				color: white;
				display:block;
				float:left;
				margin-right: 6px;
			}
			.var {
				background:#3B6CD7;
			}
			code {
				background-color: #FFECB5;
				color:black;
				font-size: 110%;
			}
			.time { color: #00A1FF; margin-right: 6px;}
			.file {
				margin-top: 7px;
				font-size: 11px;
				color:gray;
				background:#F0F0F0;
				border-bottom: 1px solid #DADADA;
				padding: 5px;
				font-family:arial;
			}
		</style>
    </head>
    <body id="general">
		<h1 id="branding">spilling the guts of your script</h1>
		<div class="doc">Event Log</div>
		<ul>
			<li><span class="label mysql">MySQL Query</span> <span class="time">15ms</span> <code><?=('SELECT * FROM `members_account` WHERE `id` = 1;'); ?></code>
				<div class="file">account.ixml[112]</div></li>
			<li><span class="label var">IXML</span> <span class="time">1.2µs</span> Requested <code>{member.id}</code> while in scope <code>1</code> and got <code>1345</code>
				<div class="file">account.ixml[112]</div></li>
			<li><span class="label var">IXML</span> <span class="time">1.2µs</span> Requested <code>{member.id}</code> while in scope <code>1</code> and got <code>1345</code>
				<div class="file">account.ixml[112]</div></li>
			<li><span class="label var">IXML</span> <span class="time">1.2µs</span> Requested <code>{member.id}</code> while in scope <code>1</code> and got <code>1345</code>
				<div class="file">account.ixml[112]</div></li>
			<li><span class="label var">IXML</span> <span class="time">1.2µs</span> Requested <code>{member.id}</code> while in scope <code>1</code> and got <code>1345</code>
				<div class="file">account.ixml[112]</div></li>
			<li><span class="label var">IXML</span> <span class="time">1.2µs</span> Requested <code>{member.id}</code> while in scope <code>1</code> and got <code>1345</code>
				<div class="file">account.ixml[112]</div></li>
			<li><span class="label var">IXML</span> <span class="time">1.2µs</span> Requested <code>{member.id}</code> while in scope <code>1</code> and got <code>1345</code>
				<div class="file">account.ixml[112]</div></li>
			<li><span class="label var">IXML</span> <span class="time">1.2µs</span> Requested <code>{member.id}</code> while in scope <code>1</code> and got <code>1345</code>
				<div class="file">account.ixml[112]</div></li>
			<li><span class="label var">IXML</span> <span class="time">1.2µs</span> Requested <code>{member.id}</code> while in scope <code>1</code> and got <code>1345</code>
				<div class="file">account.ixml[112]</div></li>
			<li><span class="label var">IXML</span> <span class="time">1.2µs</span> Requested <code>{member.id}</code> while in scope <code>1</code> and got <code>1345</code>
				<div class="file">account.ixml[112]</div></li>
			<li><span class="label var">IXML</span> <span class="time">1.2µs</span> Requested <code>{member.id}</code> while in scope <code>1</code> and got <code>1345</code>
				<div class="file">account.ixml[112]</div></li>
			<li><span class="label var">IXML</span> <span class="time">1.2µs</span> Requested <code>{member.id}</code> while in scope <code>1</code> and got <code>1345</code>
				<div class="file">account.ixml[112]</div></li>
			<li><span class="label var">IXML</span> <span class="time">1.2µs</span> Requested <code>{member.id}</code> while in scope <code>1</code> and got <code>1345</code>
				<div class="file">account.ixml[112]</div></li>
			<li><span class="label var">IXML</span> <span class="time">1.2µs</span> Requested <code>{member.id}</code> while in scope <code>1</code> and got <code>1345</code>
				<div class="file">account.ixml[112]</div></li>
		</ul>
		<?=$message?>
		<?if($doc):?>
		<div class="doc">Additional Documentation</div>
		<?=$doc?>
		<?endif;?>
    </body>
</html>