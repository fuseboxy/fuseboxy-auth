<?php /*
<fusedoc>
	<io>
		<in>
			<structure name="$xfa">
				<string name="submit" optional="yes" />
			</structure>
		</in>
		<out>
			<structure name="new_password" scope="form" optional="yes" oncondition="xfa.submit" />
			<structure name="confirm_password" scope="form" optional="yes" oncondition="xfa.submit" />
		</out>
	</io>
</fusedoc>
*/ ?>
<form id="account-password" class="pt-3" method="post" action="<?php echo F::url($xfa['submit']); ?>">
	<div class="form-group row">
		<label class="col-2 col-form-label text-right">New password</label>
		<div class="col-4">
			<input type="password" class="form-control input-sm" name="new_password" />
		</div>
	</div>
	<div class="form-group row">
		<label class="col-2 col-form-label text-right">Confirm password</label>
		<div class="col-4">
			<input type="password" class="form-control input-sm" name="confirm_password" />
		</div>
	</div>
	<div class="form-group row mt-4">
		<div class="col-10 offset-2">
			<button type="submit" class="btn btn-primary">Save changes</button>
		</div>
	</div>
</form>