<?php /*
<fusedoc>
	<io>
		<in>
			<structure name="$xfa">
				<string name="submit" />
				<string name="forgot" optional="yes" />
				<string name="login" optional="yes" />
			</structure>
			<structure name="$layout">
				<string name="content" />
				<string name="captcha" optional="yes" />
			</structure>
			<structure name="$authLayout">
				<string name="flash" />
				<string name="logo" optional="yes" />
				<string name="brand" optional="yes" />
				<string name="title" optional="yes" />
			</structure>
		</in>
		<out />
	</io>
</fusedoc>
*/ ?>
<form id="auth-form" method="post" class="container my-5" action="<?php if ( isset($xfa['submit']) ) echo F::url($xfa['submit']); ?>"><?php
	// form size
	?><div class="col-12 col-md-10 col-lg-8 col-xl-6 offset-md-1 offset-lg-2 offset-xl-3 pt-5"><?php
		// logo
		if ( !empty($authLayout['logo']) ) :
			?><h1 class="logo text-center mb-5"><img src="<?php echo $authLayout['logo']; ?>" /></h1><?php
		endif;
		?><div class="card"><?php
			// header
			if ( !empty($authLayout['brand']) or !empty($authLayout['title']) ) :
				?><a class="btn pt-4" href="<?php echo F::url($fusebox->controller); ?>"><?php
					if ( !empty($authLayout['brand']) ) echo "<h3 class='font-weight-normal mt-3'>{$authLayout['brand']}</h3>";
					if ( !empty($authLayout['title']) ) echo "<h5 class='font-weight-light text-muted'>{$authLayout['title']}</h5>";
				?></a><?php
			endif;
			// separator
			if ( !F::is('*.index') ) :
				?><hr class="w-50 mx-auto mt-4" /><?php
			endif;
			// content
			?><div class="card-body px-3 mx-3"><?php
				if ( !empty($authLayout['flash']) ) echo $authLayout['flash'];
				if ( !empty($layout['content']) ) echo $layout['content'];
			?></div><?php
			// footer
			if ( !empty($layout['captcha']) ) :
				?><div class="mb-3 pb-4 text-center"><?php echo $layout['captcha']; ?></div><?php
			endif;
			// link
			if ( isset($xfa['forgot']) or isset($xfa['login']) ) :
				?><div class="card-footer text-center py-4 small"><?php
					if ( isset($xfa['forgot']) ) :
						?><a href="<?php echo F::url($xfa['forgot']); ?>">Forgot you password?</a><?php
					endif;
					if ( isset($xfa['login']) ) :
						?><a href="<?php echo F::url($xfa['login']); ?>">Yes, I have username and password.</a><?php
					endif;
				?></div><?php
			endif;
		?></div><!--/.card-->
	</div><!--/.col-->
</form>