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
<form
	id="account-password"
	class="form-horizontal"
	<?php if ( isset($xfa['submit']) ) : ?>
		method="post"
		action="<?php echo F::url($xfa['submit']); ?>"
	<?php else : ?>
		onsubmit="return false;"
	<?php endif; ?>
>
	<div class="form-group">
		<label class="control-label col-xs-2">New password</label>
		<div class="col-xs-3"><input type="password" class="form-control input-sm" name="new_password" /></div>
	</div>
	<div class="form-group">
		<label class="control-label col-xs-2">Confirm password</label>
		<div class="col-xs-3"><input type="password" class="form-control input-sm" name="confirm_password" /></div>
	</div>
	<?php if ( isset($xfa['submit']) ) : ?>
		<br />
		<div class="form-group">
			<div class="col-xs-10 col-xs-offset-2">
				<button type="submit" class="btn btn-primary">Submit</button>
			</div>
		</div>
	<?php endif; ?>
</form>