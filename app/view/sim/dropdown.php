<?php /*
<fusedoc>
	<io>
		<in>
			<structure name="$xfa">
				<string name="start" />
				<string name="end" />
			</structure>
			<structure name="$users">
				<structure name="~role~">
					<object name="~id~">
						<number name="id" />
						<string name="role" />
						<string name="username" />
						<string name="fullname" />
					</object>
				</structure>
			</structure>
		</in>
		<out>
			<number name="user_id" scope="url" oncondition="xfa.start" />
		</out>
	</io>
</fusedoc>
*/ ?>
<ul id="sim-dropdown" class="p-0 list-unstyled">
	<li class="dropdown-header h6">USER SIMULATION</li>
	<?php foreach ( $users as $role => $arr ) : ?>
		<li class="dropdown-divider"></li>
		<li class="dropdown-header py-1"><small><?php echo $role; ?></small></li>
		<?php foreach ( $arr as $id => $item ) : ?>
			<li>
				<a
					href="<?php echo F::url($xfa['start'].'&user_id='.$id); ?>"
					class="dropdown-item <?php if ( Sim::user('id') == $id ) echo 'active'; ?>"
				><?php echo $item->username; ?></a>
			</li>
		<?php endforeach; ?>
	<?php endforeach; ?>
	<?php if ( isset($xfa['end']) ) : ?>
		<li class="dropdown-divider"></li>
		<li>
			<a href="<?php echo F::url($xfa['end']); ?>" class="dropdown-item">
				<i class="fa fa-ban mr-1"></i> End Sim
			</a>
		</li>
	<?php endif; ?>
</ul>