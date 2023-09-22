<?php
    switch ($request['view'] ?? '')
    {
		case 'form':
            $content = $vmblast->showPage('form');
        break;

		case '':
        case 'list':
        default:
            $content = $vmblast->showPage('grid');
        break;
    }

	if (!empty($conflict_url)) {
		$conflicthtml = '<div class="well">';
		$conflicthtml .= "<h5>"._("Conflicting Extensions")."</h5>";
		$conflicthtml .= implode('<br .>',$conflict_url);
		$conflicthtml .= '</div>';
	}
?>
<div class="container-fluid">
	<h1><?php echo _("Voicemail Blasting") ?></h1>
	<?php echo $conflicthtml ?? ''; ?>
	<div class = "display full-border">
		<div class="row">
			<div class="col-sm-12">
				<div class="fpbx-container">
					<div class="display full-border">
						<?php echo $content ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="modules/vmblast/assets/js/vmblast.js"></script>
