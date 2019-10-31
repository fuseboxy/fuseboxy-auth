<?php /*
<fusedoc>
	<io>
		<in>
			<structure name="$xfa">
				<string name="submit" />
				<string name="forgot" optional="yes" comments="ajax-load" />
			</structure>
			<structure name="$layout">
				<string name="captcha" optional="yes" />
			</structure>
		</in>
		<out>
			<structure name="data" scope="form" oncondition="xfa.submit">
				<string name="username" />
				<string name="password" />
				<number name="remember" />
			</structure>
		</out>
	</io>
</fusedoc>
*/ ?>
<div id="auth-login" class="pt-2 pb-1">
	<form role="form" class="px-2" method="post" action="<?php echo F::url($xfa['submit']); ?>">
		<div class="form-group">
			<div class="input-group">
				<div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-fw fa-user"></i></span></div>
				<input class="form-control" type="text" name="data[username]" placeholder="Username or email" required autofocus />
			</div>
		</div>
		<div class="form-group">
			<div class="input-group">
				<div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-fw fa-key"></i></span></div>
				<input class="form-control" type="password" name="data[password]" placeholder="Password" required />
			</div>
		</div>
		<div class="form-check small ml-1 mt-n2">
			<input id="auth-login-remember" name="data[remember]" class="form-check-input" type="checkbox" value="30" />
			<label class="form-check-label text-muted" for="auth-login-remember">Remember me</label>
		</div>
		<?php if ( !empty($layout['captcha']) ) : ?>
			<div class="form-group text-center pt-4 mb-n2">
				<div><?php echo $layout['captcha']; ?></div>
			</div>
		<?php endif; ?>
		<div class="form-group text-center pt-4">
			<button type="submit" class="btn btn-primary">Sign in</button>
		</div>
	</form>
	<?php if ( isset($xfa['forgot']) ) : ?>
		<div class="border-top px-2 pt-3">
			<a 
				href="<?php echo F::url($xfa['forgot']); ?>" 
				data-toggle="ajax-load" 
				data-target="#auth-login" 
				data-toggle-loading="none"
				class="small font-italic"
			>Forgot password?</a>
		</div>
	<?php endif; ?>
</div>