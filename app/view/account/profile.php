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

<form
	id="account-profile"
	class="form-horizontal"
	<?php if ( isset($xfa['submit']) ) : ?>
		method="post"
		action="<?php echo F::url($xfa['submit']); ?>"
	<?php else : ?>
		onsubmit="return false;"
	<?php endif; ?>
>
	<div class="form-group">
		<label class="control-label col-xs-2">Username</label>
		<div class="col-xs-3"><p class="form-control-static"><?php echo $user->username; ?> &nbsp;(<?php echo $user->role; ?>)</p></div>
	</div>
	<div class="form-group">
		<label class="control-label col-xs-2">Full name</label>
		<div class="col-xs-3"><input type="text" class="form-control input-sm" name="data[full_name]" value="<?php echo $user->full_name; ?>" /></div>
	</div>
	<div class="form-group">
		<label class="control-label col-xs-2">Email</label>
		<div class="col-xs-3"><input type="text" class="form-control input-sm" name="data[email]" value="<?php echo $user->email; ?>" /></div>
	</div>
	<div class="form-group">
		<label class="control-label col-xs-2">Tel</label>
		<div class="col-xs-3"><input type="text" class="form-control input-sm" name="data[tel]" value="<?php echo $user->tel; ?>" /></div>
	</div>
	<?php if ( isset($xfa['submit']) ) : ?>
		<br />
		<div class="form-group">
			<div class="col-xs-10 col-xs-offset-2">
				<button type="submit" class="btn btn-primary">Save changes</button>&nbsp;
				<button type="reset" class="btn btn-default">Reset</button>
			</div>
		</div>
	<?php endif; ?>
</div>