<?php /*
<fusedoc>
	<io>
		<in>
			<structure name="$xfa">
				<string name="sso" />
				<string name="local" />
			</structure>
		</in>
		<out />
	</io>
</fusedoc>
*/ ?>
<div id="auth-index" class="px-2 pt-2 pb-0">
	<div class="form-group">
		<a href="<?php echo F::url($xfa['sso']); ?>" class="btn btn-sso btn-lg btn-block py-3 font-weight-light btn-primary">Single Sign-On</a>
	</div>
	<div class="form-group">
		<a href="<?php echo F::url($xfa['local']); ?>" class="btn btn-login btn-lg btn-block py-3 font-weight-light btn-outline-primary text-primary bg-white">Local Account Login</a>
	</div>
</div>