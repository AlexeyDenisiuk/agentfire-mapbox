<?php

	use AGFM\Helpers\AGFM_Helpers;

?>

<div id="agfm_main_map_app_cont">
	<div class="row mt-3">
	  	<div id="filters_cont" class="col-md-3">
			<div class="input-group">
				<input type="text" id="filters_map_marker_name" class="form-control" placeholder="<?=AGFM_Helpers::__( 'Search' )?>">
				<div class="input-group-append">
					<button id="filters_map_marker_search_btn" class="btn btn-secondary btn-search">
						<i class="fas fa-search" ></i>
					</button>
				</div>
			</div>
			<div class="row mt-3 mb-3">
				<div class="col">
					<div class="select-cont">
						<!-- style="width: 100%;" - this is by Select2 owners manual to make it responsive -->
						<select id="filters_map_marker_tags" name="tags[]" class="agfm-select" style="width: 100%;" multiple="multiple" placeholder="<?=AGFM_Helpers::__( 'Select a tag' )?>">
							<?php foreach ( $tmpl['map_marker_tags'] as $tag ) { ?>
								<option value='<?=$tag->term_id?>'><?=$tag->name?></option>
							<?php } ?>
						</select>
					</div>
				</div>
			</div>
	  	</div>
	    <div class="col-md-9">
			<div class="map-cont">

				<!-- do we need to hide edit markers button? -->
				<?php if ( $tmpl['current_user_id'] ) { ?>
						<div id="btn_edit_map" class="btn btn_edit-map"><i class="fas fa-wrench"></i></div>
				<?php } ?>

			    <div id='map' class="map"></div>
			</div>
	    </div>
	</div>

	<div class="modal fade" id="popup_window_add_map_marker" tabindex="-1" role="dialog" aria-labelledby="popup_window_add_map_marker" aria-hidden="true" data-keyboard="false" data-backdrop="static">
		<div class="modal-dialog modal-dialog-centered" role="document">
		    <div class="modal-content">
				<div class="modal-header border-white">
					<h5 class="modal-title"><?=AGFM_Helpers::__( 'Add marker' )?></h5>
				</div>
		      	<div class="modal-body">
		    		<div class="col-md-9">
						<form>
							<div class="input-group">
								<input id="new_marker_name" type="text" class="form-control" placeholder="Name" required>
						        <div class="invalid-feedback"><?=AGFM_Helpers::__( 'Choose a name please' )?></div>
							</div>
							<div class="row mt-3 mb-3">
								<div class="col">
									<div class="select-cont">
										<!-- style="width: 100%;" - this is by Select2 owners manual to make it responsive -->
										<select class="agfm-select form-control" style="width: 100%" multiple="multiple" placeholder="<?=AGFM_Helpers::__( 'Select a tag' )?>">
											<?php foreach ( $tmpl['map_marker_tags'] as $tag ) { ?>
												<option value='<?=$tag->term_id?>'><?=$tag->name?></option>
											<?php } ?>
										</select>
									</div>
									<div class="invalid-feedback"><?=AGFM_Helpers::__( 'Choose a tag please' )?></div>
								</div>
							</div>
						</form>
					</div>
		      	</div>
				<div class="modal-footer border-white">
					<button type="button" id="map_marker_add_btn" class="btn btn-primary"><?=AGFM_Helpers::__( 'Add' )?></button>
					<button type="button" id="map_marker_cancel_btn" class="btn btn-light bg-white border-white shadow-none" data-dismiss="modal"><?=AGFM_Helpers::__( 'Cancel' )?></button>
				</div>
		    </div>
		</div>
	</div>
</div>