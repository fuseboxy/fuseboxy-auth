<?php /*
<fusedoc>
	<io>
		<in>
			<structure name="data" scope="$arguments" optional="yes">
				<string name="email" />
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
<div id="auth-forgot">
	<p class="pt-3 pb-2">Enter your email address below and we will send you a link to reset your password.</p>
	<div class="form-group">
		<label><sub class="text-muted font-weight-bold">Email address</sub></label>
		<div class="input-group">
			<span class="input-group-prepend"><span class="input-group-text px-3 bg-light text-dark"><i class="fa fa-fw fa-envelope"></i></span></span>
			<input 
				type="text" 
				name="data[email]" 
				class="form-control form-control-lg" 
				value="<?php if ( isset($arguments['data']['email']) ) echo $arguments['data']['email']; ?>"
				required 
				autofocus 
			/>
		</div>
	</div>
	<div class="form-group pt-3">
		<button type="submit" class="btn btn-lg btn-block btn-primary font-weight-light">Reset Password</button>
	</div>
</div>