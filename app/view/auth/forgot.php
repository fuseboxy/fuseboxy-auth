<?php /*
<fusedoc>
	<io>
		<in>
			<structure name="$xfa">
				<string name="submit" />
				<string name="login" optional="yes" comments="ajax-load" />
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
				<input class="form-control" type="text" name="data[email]" placeholder="Email Address" required autofocus />
			</div>
		</div>
		<div class="form-group text-center mt-3 pt-4">
			<button type="submit" class="btn btn-light">Send</button>
		</div>
	</form>
	<?php if ( isset($xfa['login']) ) : ?>
		<div class="border-top px-2 pt-3">
			<a 
				href="<?php echo F::url($xfa['login']); ?>"
				data-toggle="ajax-load"
				data-target="#auth-forgot"
				data-toggle-transition="fade"
				data-toggle-loading="none"
				class="small font-italic"
			>Yes, I have username and password.</a>
		</div>
	<?php endif; ?>
</div>