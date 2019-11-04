<?php /*
<fusedoc>
	<io>
		<in>
			<structure name="$xfa">
				<string name="auth" />
			</structure>
			<structure name="$layout">
				<string name="panelTitle" />
				<string name="panelSubtitle" />
			</structure>
			<structure name="$authLayout">
				<string name="flash" />
			</structure>
			<structure name="$layout">
				<string name="content" />
			</structure>
		</in>
		<out />
	</io>
</fusedoc>
*/

?>
<div id="auth-panel" class="container" style="margin-top: 50vh; transform: translateY(-60%);">
	<div class="col-12 col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-5 offset-lg-3">
		<div class="card panel-default">
			<div class="card-header text-center">
				<a href="<?php echo F::url($xfa['auth']); ?>" class="btn">
					<h3><?php echo $layout['panelTitle']; ?></h3>
					<h5 class="text-muted"><?php echo $layout['panelSubtitle']; ?></h5>
				</a>
			</div>
			<div class="card-body"><?php
				if ( isset($authLayout['flash']) ) echo $authLayout['flash'];
				if ( isset($layout['content']) ) echo $layout['content'];
			?></div>
		</div>
	</div>
</div>