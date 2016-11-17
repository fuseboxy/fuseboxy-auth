<form id="auth-forgot" class="form-horizontal col-md-12" role="form" method="post" action="<?php echo F::url($xfa['submit']); ?>">
	<div class="row text-muted small">To reset the password, please enter your email.</div>
	<br />
	<div class="form-group">
		<div class="input-group">
			<span class="input-group-addon"><i class="fa fa-envelope-o"></i></span>
			<input class="form-control" type="text" name="data[email]" placeholder="Email Address" required autofocus />
		</div>
	</div>
	<br />
	<div class="form-group text-center">
		<input type="submit" class="btn btn-default" value="Send" />
	</div>
	<?php if ( isset($xfa['login']) ) : ?>
		<div class="form-group">
			<hr style="margin-bottom: 1em; margin-top: .5em" />
			<a class="small" href="<?php echo F::url($xfa['login']); ?>" data-toggle="ajax-load" data-target="#auth-forgot" data-toggle-transition="fade" data-toggle-loading="none">
				<em>Yes, I have username and password.</em>
			</a>
		</div>
	<?php endif; ?>
</form>