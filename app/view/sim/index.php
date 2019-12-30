<?php /*
<fusedoc>
	<io>
		<in>
			<structure name="$xfa">
				<string name="start" />
			</structure>
			<structure name="$users">
				<object name="~id~">
					<number name="id" />
					<string name="username" />
					<string name="fullname" />
				</object>
			</structure>
		</in>
		<out>
			<number name="user_id" scope="url" oncondition="xfa.start" />
		</out>
	</io>
</fusedoc>
*/ ?>
<div id="sim-index">
	<ul>
		<?php foreach ( $users as $id => $item ) : ?>
			<li>
				<?php if ( !empty($item['fullname']) ) : ?>
					<em class="small text-muted float-right"><?php echo $item['fullname']; ?></em>
				<?php endif; ?>
				<a href="<?php echo F::url("{$xfa['start']}&user_id={$id}"); ?>">
					<?php echo $item['username']; ?>
				</a>
			</li>
		<?php endif; ?>
	</ul>
</div>