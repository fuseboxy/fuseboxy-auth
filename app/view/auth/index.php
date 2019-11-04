<?php /*
<fusedoc>
	<io>
		<in>
			<structure name="$xfa">
				<string name="cas" />
				<string name="local" />
			</structure>
		</in>
		<out />
	</io>
</fusedoc>
*/ ?>
<div id="auth-index" class="px-2 pt-2 pb-0">
	<div class="form-group">
		<a 
			href="<?php echo F::url($xfa['cas']); ?>" 
			class="btn w-100 py-3 btn-primary"
		>CAS Login</a>
	</div>
	<div class="form-group">
		<a 
			href="<?php echo F::url($xfa['local']); ?>" 
			class="btn w-100 py-3 btn-outline-primary text-primary bg-white"
		>Local Account Login</a>
	</div>
</div>