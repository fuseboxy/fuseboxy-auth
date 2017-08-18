<?php /*
<fusedoc>
	<io>
		<in>
			<string name="flash" scope="$arguments|$_SESSION" />
			<structure name="flash" scope="$arguments|$_SESSION">
				<string name="type" optional="yes" default="warning" comments="success|info|warning|danger" />
				<string name="icon" optional="yes" />
				<string name="title" optional="yes" />
				<string name="message" />
			</structure>
		</in>
		<out />
	</io>
</fusedoc>
*/
// cross-page
if ( isset($_SESSION['flash']) ) {
	$arguments['flash'] = $_SESSION['flash'];
	unset($_SESSION['flash']);
}
// default
if ( isset($arguments['flash']) ) {
	if ( !is_array($arguments['flash']) ) {
		$arguments['flash'] = array('message' => $arguments['flash']);
	}
	if ( empty($arguments['flash']['type']) ) {
		$arguments['flash']['type'] = 'warning';
	}
}
?>
<?php if ( isset($arguments['flash']) ) : ?>
	<div id="auth-flash" class="alert alert-<?php echo $arguments['flash']['type']; ?>">
		<?php if ( !empty($arguments['flash']['icon']) ) : ?>
			<i class="<?php echo $arguments['flash']['icon']; ?>"></i>
		<?php endif; ?>
		<?php if ( !empty($arguments['flash']['title']) ) : ?>
			<strong><?php echo $arguments['flash']['title']; ?></strong>
		<?php endif; ?>
		<?php echo $arguments['flash']['message']; ?>
	</div>
<?php endif; ?>