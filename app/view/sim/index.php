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
<div id="sim-index">
	<div class="modal-header">
		<div class="modal-title h5">User Simulation</div>
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	</div>
	<div class="modal-body">
		<?php foreach ( $users as $role => $arr ) : ?>
			<?php if ( $role != array_keys($users)[0] ) : ?>
				<hr class="row" />
			<?php endif; ?>
			<div class="px-3 pt-2 pb-2">
				<h6 class="mb-3 font-weight-normal <?php echo in_array(Sim::user('id'), array_keys($arr)) ? 'text-primary' : 'text-muted'; ?>">
					<?php echo $role; ?>
				</h6>
				<?php foreach ( $arr as $id => $item ) : ?>
					<a
						class="badge <?php echo ( Sim::user('id') == $id ) ? 'badge-primary' : 'badge-light'; ?>"
						href="<?php echo F::url($xfa['start'].'&user_id='.$id); ?>"
					><?php echo $item->username; ?></a>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
	</div>
	<div class="modal-footer">
		<?php if ( isset($xfa['end']) ) : ?>
			<a href="<?php echo F::url($xfa['end']); ?>" class="btn btn-light"><i class="fa fa-ban mr-1"></i> End Sim</a>
		<?php else : ?>
			<button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
		<?php endif; ?>
	</div>
</div>