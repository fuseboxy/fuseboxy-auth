<form id="auth-login" class="form-horizontal col-md-12" role="form" method="post" action="<?php echo F::url($xfa['submit']); ?>">
	<div class="form-group">
		<div class="input-group">
			<span class="input-group-addon"><i class="fa fa-user"></i></span>
			<input class="form-control" type="text" name="data[username]" placeholder="Username or Email" required autofocus />
		</div>
	</div>
	<div class="form-group">
		<div class="input-group">
			<span class="input-group-addon"><i class="fa fa-key"></i></span>
			<input class="form-control" type="password" name="data[password]" placeholder="Password" required />
		</div>
		<div class="checkbox" style="padding-left: 2em; padding-top: 1em;">
			<label>
				<input type="checkbox" name="data[remember]" value="30" /> Remember me
			</label>
		</div>
	</div>
	<div class="form-group text-center">
		<input type="submit" class="btn btn-primary" value="Sign in" />
	</div>
	<?php if ( isset($xfa['forgot']) ) : ?>
		<div class="form-group">
			<hr style="margin-bottom: 1em; margin-top: .5em" />
			<a class="small" href="<?php echo F::url($xfa['forgot']); ?>" data-toggle="ajax-load" data-target="#auth-login" data-toggle-loading="none">
				<em>Forgot password?</em>
			</a>
		</div>
	<?php endif; ?>
</form>