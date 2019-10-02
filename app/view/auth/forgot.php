<?php /*
<fusedoc>
	<io>
		<in>
			<structure name="$xfa">
				<string name="submit" />
				<string name="login" optional="yes" />
			</structure>
			<structure name="$layout">
				<string name="captcha" optional="yes" />
			</structure>
		</in>
		<out>
			<structure name="data" scope="form" oncondition="xfa.submit">
				<string name="email" />
			</structure>
		</out>
	</io>
</fusedoc>
*/ ?>
<div id="auth-forgot" class="pb-1">
	<form class="px-2" role="form" method="post" action="<?php echo F::url($xfa['submit']); ?>">
		<div class="form-group">
			<label class="small text-muted">To reset the password, please enter your email:</label>
			<div class="input-group">
				<span class="input-group-prepend"><span class="input-group-text"><i class="fa fa-envelope"></i></span></span>
				<input class="form-control" type="text" name="data[email]" placeholder="Email address" required autofocus />
			</div>
		</div>
		<?php if ( !empty($layout['captcha']) ) : ?>
			<div class="form-group text-center pt-2 mb-n4">
				<div><?php echo $layout['captcha']; ?></div>
			</div>
		<?php endif; ?>
		<div class="form-group text-center mt-3 pt-4">
			<button type="submit" class="btn btn-light">Send</button>
		</div>
	</form>
	<?php if ( isset($xfa['login']) ) : ?>
		<div class="border-top px-2 pt-3">
			<a 
				href="<?php echo F::url($xfa['login']); ?>"
				class="small font-italic"
				<?php if ( empty($layout['captcha']) ) : ?>
					data-toggle="ajax-load"
					data-target="#auth-forgot"
					data-toggle-transition="fade"
					data-toggle-loading="none"
				<?php endif; ?>
			>Yes, I have username and password.</a>
		</div>
	<?php endif; ?>
</div>