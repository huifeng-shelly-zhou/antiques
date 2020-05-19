
<?php			
	
	$apis = array(
		( object )array(
			'name' => 'User | Login',
			'link' => '/OX/api/user/login',
			'type' => 'post',
			'headers' => array(
					'Key' => 'string: api key, example: abcederoternoc',
					'Secret' => 'string: api secret, example: abcederoternocabcederoternocabcederoternoc',
				),
			'parameters' => array(					
					'authorize' => 'string: base64 {email:password}, example: c3RhcmxvYnN0ZXJAYWl0c29sdXRpb24uY2E6MkhYcSRCeTMlZ09sS0tKcURybUhuOG05',					
				),
			'response' => json_encode( new ANTIQUES_USER , JSON_PRETTY_PRINT ),		
		),
		
		( object )array(
			'name' => 'User | Register',
			'link' => '/OX/api/user/register',
			'type' => 'post',
			'headers' => array(
					'Key' => 'string: api key, example: abcederoternoc',
					'Secret' => 'string: api secret, example: abcederoternocabcederoternocabcederoternoc',										
				),
			'parameters' => array(					
					'authorize' => 'string: base64 {email:password}, example: c3RhcmxvYnN0ZXJAYWl0c29sdXRpb24uY2E6MkhYcSRCeTMlZ09sS0tKcURybUhuOG05',					
					'first_name' => 'optional; string',
					'last_name' => 'optional; string'
				),
			'response' => json_encode( array(
										'success'=>true, 
										'message'=> '注册成功！请到您的电子邮箱查看您的验证电子邮件！',																			
						), JSON_PRETTY_PRINT ),		
		),
		
		( object )array(
			'name' => 'User | Profile',
			'link' => '/OX/api/user/profile',
			'type' => 'post',
			'headers' => array(
					'Key' => 'string: api key, example: abcederoternoc',
					'Secret' => 'string: api secret, example: abcederoternocabcederoternocabcederoternoc',									
				),
			'parameters' => array(					
					'token' => 'string: token generated by /OX/api/user/login',
				),
			'response' => json_encode( array(), JSON_PRETTY_PRINT ),		
		),
		
		( object )array(
			'name' => 'User | Password Reset',
			'link' => '/OX/api/user/password/reset',
			'type' => 'post',
			'headers' => array(
					'Key' => 'string: api key, example: abcederoternoc',
					'Secret' => 'string: api secret, example: abcederoternocabcederoternocabcederoternoc',										
				),
			'parameters' => array(					
					'email' => 'string: base64 {email}, example: c2hlbGx5Lnpob3UrMTJAYWl0c29sdXRpb24uY2E=',					
				),
			'response' => json_encode( array('success'=>true, 'message'=> 'Reset password success! Please find your new password in your email!'), JSON_PRETTY_PRINT),		
		),
		
		( object )array(
			'name' => 'User | Password Update',
			'link' => '/OX/api/user/password/update',
			'type' => 'post',
			'headers' => array(
					'Key' => 'string: api key, example: abcederoternoc',
					'Secret' => 'string: api secret, example: abcederoternocabcederoternocabcederoternoc',										
				),
			'parameters' => array(					
					'new_password' => 'string: base64 {password}, example: U2hvcDEyMzMyMQ==',
					'token' => 'string: token generated by /OX/api/user/login',
				),
			'response' => json_encode( array(
										'success'=>true, 
										'message'=> 'Update password success!',
										'token' => 'e5a54615-11e3-4808-a9aa-dc31ebbb2fd4-972c89',
										'token_expiration' => '2019-12-12T16:10:31+00:00',										
						), JSON_PRETTY_PRINT),		
		),
		
		( object )array(
			'name' => 'User | Validate Token',
			'link' => '/OX/api/user/validate',
			'type' => 'post',
			'headers' => array(
					'Key' => 'string: api key, example: abcederoternoc',
					'Secret' => 'string: api secret, example: abcederoternocabcederoternocabcederoternoc',										
				),
			'parameters' => array(
					'token' => 'string: token generated by /OX/api/user/login',
				),
			'response' => json_encode( array(
										'success'=>false, 
										'message'=> '',
										'display_name' => '',
										'email' => '',										
						), JSON_PRETTY_PRINT),		
		),
		
		
		
		( object )array(
			'name' => 'Category | List',
			'link' => '/v1/category/list',
			'type' => 'post',
			'headers' => array(
					'Key' => 'string: api key, example: abcederoternoc',
					'Secret' => 'string: api secret, example: abcederoternocabcederoternocabcederoternoc',									
				),
			'parameters' => array(					
					'lang' => 'optional; string: hk, cn, en; default: hk;',					
				),
			'response' => json_encode( array(), JSON_PRETTY_PRINT ),		
		),
		
		
	);

?>
<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<!-- Bootstrap CSS -->
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
	
	<title>API REST</title>

</head>

<body>
	<header class="navbar navbar-expand navbar-dark flex-column flex-md-row bd-navbar">
		<h3 style="color:darkgoldenrod">RESTful APIs</h3>
	</header>


	<div class="container-fluid">
		<!-- Content here -->
		<div class="row bg">
			<div class="w-100">
				<div class="accordion" id="api-list">
					<?php	foreach ($apis as $idx=>$api) :	?>
					<div class="card">
						<div class="card-header" id="heading-<?php	echo $idx; ?>">
							<h5 class="mb-0">
								<button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse-<?php	echo $idx; ?>" aria-expanded="false" aria-controls="collapse-<?php	echo $idx; ?>">
								  <?php	echo $api->name;	?>
								</button>
							</h5>
						
						</div>

						<div id="collapse-<?php	echo $idx; ?>" class="collapse" aria-labelledby="heading-<?php	echo $idx; ?>" data-parent="#api-list">
							<div class="card-body">
								<div class="row">
									<div class="col-lg-12 col-md-12 col-xs-12">
										<p class="api-link"><span class="type-<?php echo $api->type;?>"><?php echo $api->type;?></span><a href="<?php echo $api->link;?>" class="alert-link" target="_blank"><?php echo $api->link;?></a></p>
									</div>
								</div>
								<?php if (isset($api->headers)) { ?>
								<h6>Headers</h6>
								<table class="table table-striped">
								  <thead>
									<tr>
									  <th scope="col">Key</th>
									  <th scope="col">Value</th>
									</tr>
								  </thead>
								  <tbody>
									 <?php	foreach ($api->headers as $key=>$val) :	?>	
									 <?php	$is_optional= (strpos($val, 'optional')!==false) ? true : false;	?>	
									<tr>
									  <td><?php	echo ($is_optional?'':'<strong style="color:#ad0000;">').$key.($is_optional?'':'</strong>');	?>	</td>
									  <td><?php	echo $val;	?>	</td>
									</tr>
									 <?php endforeach; ?>
								  </tbody>								  
								</table>
								<?php } ?>
								<?php if (isset($api->parameters)) { ?>
								<h6>Parameters</h6>
								<table class="table table-striped">
								  <thead>
									<tr>
									  <th scope="col">Key</th>
									  <th scope="col">Value</th>
									</tr>
								  </thead>
								  <tbody>
									 <?php	foreach ($api->parameters as $key=>$val) :	?>	
									 <?php	$is_optional= (strpos($val, 'optional')!==false) ? true : false;	?>	
									<tr>
									  <td><?php	echo ($is_optional?'':'<strong style="color:#ad0000;">').$key.($is_optional?'':'</strong>');	?>	</td>
									  <td><?php	echo $val;	?>	</td>
									</tr>
									 <?php endforeach; ?>
								  </tbody>								  
								</table>
								<?php } ?>								
								<h6>Response</h6>
								<pre> <?php	echo $api->response;	?>	</pre>
							</div>
						</div>
					</div>
					<?php	endforeach;	?>
				</div>
			</div>
		</div>
	</div>





	<!-- Optional JavaScript -->
	<!-- jQuery first, then Popper.js, then Bootstrap JS -->
	<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>

</body>
</html>