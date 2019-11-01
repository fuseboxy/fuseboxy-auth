<?php /*
<fusedoc>
	<io>
		<in>
			<structure name="$xfa">
				<string name="submit" optional="yes" />
			</structure>
			<object name="$user">
				<number name="id" />
				<string name="role" />
				<string name="username" />
				<string name="fullname" />
				<string name="password" />
				<string name="email" />
				<string name="tel" />
			</object>
		</in>
		<out>
			<structure name="data" scope="form" optional="yes" oncondition="xfa.submit" />
		</out>
	</io>
</fusedoc>
*/ ?>

<form id="account-profile" class="pt-3" method="post" action="<?php echo F::url($xfa['submit']); ?>">
	<div class="form-group row">
		<label class="col-2 col-form-label text-right">Username</label>
		<div class="col-4">
			<p class="form-control-plaintext"><strong class="mr-1"><?php echo $user->username; ?></strong> <small>(<?php echo $user->role; ?>)</small></p>
		</div>
	</div>
	<div class="form-group row">
		<label class="col-2 col-form-label text-right">Full name</label>
		<div class="col-4">
			<input type="text" class="form-control input-sm" name="data[fullname]" value="<?php echo $user->fullname; ?>" />
		</div>
	</div>
	<div class="form-group row">
		<label class="col-2 col-form-label text-right">Email</label>
		<div class="col-4">
			<input type="text" class="form-control input-sm" name="data[email]" value="<?php echo $user->email; ?>" />
		</div>
	</div>
	<div class="form-group row">
		<label class="col-2 col-form-label text-right">Phone</label>
		<div class="col-4">
			<input type="text" class="form-control input-sm" name="data[tel]" value="<?php echo $user->tel; ?>" />
		</div>
	</div>
	<div class="form-group row mt-4">
		<div class="col-10 offset-2">
			<button type="submit" class="btn btn-primary mr-1">Save changes</button>
			<button type="reset" class="btn btn-link text-dark">Cancel</button>
		</div>
	</div>
</form>