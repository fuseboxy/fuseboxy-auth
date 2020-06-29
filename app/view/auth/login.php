<?php /*
<fusedoc>
	<io>
		<in>
			<structure name="data" scope="$arguments" optional="yes">
				<string name="username" />
			</structure>
		</in>
		<out>
			<structure name="data" scope="form" oncondition="xfa.submit">
				<string name="username" />
				<string name="password" />
			</structure>
		</out>
	</io>
</fusedoc>
*/ ?>
<div id="auth-login" class="pb-1">
	<div class="form-group">
		<label><sub class="text-muted font-weight-bold">Username or email</sub></label>
		<div class="input-group">
			<div class="input-group-prepend"><span class="input-group-text px-3 bg-light text-dark"><i class="fa fa-fw fa-user"></i></span></div>
			<input 
				type="text" 
				name="data[username]" 
				class="form-control form-control-lg" 
				value="<?php if ( isset($arguments['data']['username']) ) echo $arguments['data']['username']; ?>"
				required 
				autofocus 
			/>
		</div>
	</div>
	<div class="form-group">
		<label><sub class="text-muted font-weight-bold">Password</sub></label>
		<div class="input-group">
			<div class="input-group-prepend"><span class="input-group-text px-3 bg-light text-dark"><i class="fa fa-fw fa-lock"></i></span></div>
			<input 
				type="password" 
				name="data[password]" 
				class="form-control form-control-lg" 
				required 
			/>
		</div>
	</div>
	<div class="form-group pt-3">
		<button type="submit" class="btn btn-lg btn-block btn-primary font-weight-light">Log In</button>
	</div>
</div>