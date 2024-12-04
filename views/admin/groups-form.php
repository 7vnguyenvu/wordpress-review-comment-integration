<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php echo $this->base->plugin->displayName; ?>

		<span>
			<?php
			_e('Cài đặt | Thêm phần "Đánh giá" ngay trong phần "Bình luận" cho bài viết của bạn!', $this->base->plugin->name);
			?>
		</span>
	</h1>

	<?php
	// Notices
	foreach ($this->notices as $type => $notices_type) {
		if (count($notices_type) == 0) {
			continue;
		}
	?>
		<div class="<?php echo (($type == 'success') ? 'updated' : $type); ?> notice">
			<?php
			foreach ($notices_type as $notice) {
			?>
				<p><?php echo $notice; ?></p>
			<?php
			}
			?>
		</div>
	<?php
	}
	?>

	<div class="wrap-inner">
		<form class="review-comment-integration-pro-plugin" name="post" method="post" action="admin.php?page=review-comment-integration-plugin-settings" enctype="multipart/form-data">
			<div id="poststuff">

				<div id="post-body" class="metabox-holder columns-2">
					<!-- Content -->
					<div id="post-body-content">
						<!-- Name -->
						<input type="hidden" name="name" id="title" value="<?php echo $group['name']; ?>" size="30" placeholder="<?php _e('Field Group Name', 'review-comment-integration-pro-plugin'); ?>" />
						<input type="hidden" name="id" id="id" value="<?php echo (isset($group['groupID']) ? $group['groupID'] : ''); ?>" />

						<div id="normal-sortables" class="meta-box-sortables ui-sortable">
							<!-- Schema Type -->
							<div class="postbox">
								<h3 class="hndle hndle-none"><?php _e('Cài đặt biểu tượng "⭐"', 'review-comment-integration-pro-plugin'); ?></h3>

								<div class="option">
									<div class="left">
										<strong><?php _e('Màu rỗng', 'review-comment-integration-pro-plugin'); ?></strong>
									</div>
									<div class="right">
										<input type="text" name="css[starBackgroundColor]" value="<?php echo $group['css']['starBackgroundColor']; ?>" class="color-picker-control" />

										<p class="description">
											<?php _e('Màu sắc của các ngôi sao rỗng (Khi không được chọn).', 'review-comment-integration-pro-plugin'); ?>
										</p>
									</div>
								</div>

								<div class="option">
									<div class="left">
										<strong><?php _e('Màu chủ đạo', 'review-comment-integration-pro-plugin'); ?></strong>
									</div>
									<div class="right">
										<input type="text" name="css[starColor]" value="<?php echo $group['css']['starColor']; ?>" class="color-picker-control" />

										<p class="description">
											<?php _e('Màu sắc của các ngôi sao được chọn (Màu được chọn để hiển thị).', 'review-comment-integration-pro-plugin'); ?>
										</p>
									</div>
								</div>

								<div class="option">
									<div class="left">
										<strong><?php _e('Khi chọn', 'review-comment-integration-pro-plugin'); ?></strong>
									</div>
									<div class="right">
										<input type="text" name="css[starInputColor]" value="<?php echo $group['css']['starInputColor']; ?>" class="color-picker-control" />

										<p class="description">
											<?php _e('Màu của ngôi sao khi được di chuột đến (Trong Form).', 'review-comment-integration-pro-plugin'); ?>
										</p>
									</div>
								</div>

								<div class="option">
									<div class="left">
										<strong><?php _e('Kích thước', 'comment-rating-field-pro-plugin'); ?></strong>
									</div>
									<div class="right">
										<input type="number" name="css[starSize]" min="16" max="128" step="1" value="<?php echo $group['css']['starSize']; ?>" />
										<?php _e('px', 'comment-rating-field-pro-plugin'); ?>

										<p class="description">
											<?php _e('Kích thước các ngôi sao (Áp dụng trên tất cả các nơi hiển thị).', 'comment-rating-field-pro-plugin'); ?>
										</p>
									</div>
								</div>
							</div>

							<!-- Fields -->
							<div class="postbox">
								<h3 class="hndle hndle-none"><?php _e('Cài đặt trường xếp hạng trong Form', 'review-comment-integration-pro-plugin'); ?></h3>
								<div class="option">
									<p class="description">
										<?php _e('Trường này sẽ được thêm vào Form để người dùng đánh giá bài viết. (Lưu ý: Tên trường không được để trống!)', 'review-comment-integration-pro-plugin'); ?>
									</p>
								</div>

								<div id="sortable">
									<?php
									// Output existing fields
									foreach ($group['fields'] as $field) {
									?>
										<div class="option">
											<div class="left">
												<!-- <strong>
													<a href="#" class="dashicons dashicons-sort"></a>
													<span><?php //_e('Field', 'review-comment-integration-pro-plugin'); 
															?> #</span>
													<span class="hierarchy"><?php //echo $field['hierarchy']; 
																			?></span>
												</strong> -->
												<strong>
													<span><?php _e('Trường', 'review-comment-integration-pro-plugin'); ?></span>
												</strong>
											</div>
											<div class="right">
												<input type="text" name="fields[label][]" value="<?php echo $field['label']; ?>" placeholder="<?php _e('Tên trường', 'review-comment-integration-pro-plugin'); ?>" />
												<select name="fields[required][]" size="1">
													<option value="0" <?php echo (($field['required'] != 1) ? ' selected' : ''); ?>>
														<?php _e('Không bắt buộc', 'review-comment-integration-pro-plugin'); ?>
													</option>
													<option value="1" <?php selected($field['required'], 1); ?>>
														<?php _e('Bắt buộc', 'review-comment-integration-pro-plugin'); ?>
													</option>
												</select>
												<input type="text" name="fields[required_text][]" value="<?php echo $field['required_text']; ?>" placeholder="<?php _e('Lời nhắc nhở khi bắt buộc...', 'review-comment-integration-pro-plugin'); ?>" />
												<input type="text" name="fields[cancel_text][]" value="<?php echo $field['cancel_text']; ?>" placeholder="<?php _e('Cancel Text', 'review-comment-integration-pro-plugin'); ?>" />
												<input type="hidden" name="fields[fieldID][]" value="<?php echo $field['fieldID']; ?>" />
											</div>
										</div>
									<?php
									}
									?>
								</div>
							</div>

							<!-- Rating Input -->
							<!-- <div class="postbox">
		                        <h3 class="hndle hndle-none"><?php //_e('Rating Input', 'review-comment-integration-pro-plugin'); 
																?></h3>
		                    </div> -->

							<?php
							// Iterate through excerpt, comment and RSS groups to output settings
							foreach (Review_Comment_Integration_Pro_Groups::get_instance()->get_output_group_types() as $key => $labels) {
							?>
								<div class="postbox">
									<h3 class="hndle hndle-none"><?php echo $labels['title']; ?></h3>

									<div class="option">
										<p class="description">
											<?php echo sprintf(__('Hiển thị xếp hạng trung bình ở phần "%s". (Mặc định ở phía "Trên cùng bài viết")', 'review-comment-integration-pro-plugin'), $labels['type']); ?>
										</p>
									</div>

									<!-- Enabled -->
									<div class="option">
										<div class="left">
											<strong><?php _e('Hiển thị', 'review-comment-integration-pro-plugin'); ?></strong>
										</div>
										<div class="right">
											<select name="<?php echo $key; ?>[enabled]" size="1" data-conditional="<?php echo $key; ?>-options">
												<option value="0" <?php selected($group[$key]['enabled'], 0); ?>>
													<?php _e('Không hiển thị', 'review-comment-integration-pro-plugin'); ?>
												</option>
												<option value="1" <?php selected($group[$key]['enabled'], 1); ?>>
													<?php _e('Hiện thị khi tồn tại đánh giá', 'review-comment-integration-pro-plugin'); ?>
												</option>
												<option value="2" <?php selected($group[$key]['enabled'], 2); ?>>
													<?php _e('Luôn hiển thị', 'review-comment-integration-pro-plugin'); ?>
												</option>
											</select>
										</div>
									</div>

									<div id="<?php echo $key; ?>-options">
										<?php
										// Average Label and Position
										if (isset($group[$key]['averageLabel'])) {
										?>
											<!-- Average Label and Position -->
											<div class="option">
												<div class="left">
													<strong><?php _e('Nhãn phía trước', 'review-comment-integration-pro-plugin'); ?></strong>
												</div>
												<div class="right">
													<input type="text" name="<?php echo $key; ?>[averageLabel]" value="<?php echo $group[$key]['averageLabel']; ?>" placeholder="<?php _e('Average Rating Label', 'review-comment-integration-pro-plugin'); ?>" />
													<span class="description">
														<?php _e('VD: <span style="color: red; font-weight: 700">Rating:</span> 5/5 ⭐⭐⭐⭐⭐ (N đánh giá)', 'review-comment-integration-pro-plugin'); ?>
													</span>
												</div>
											</div>
										<?php
										}
										?>
									</div>
									<!-- ./extra-options -->
								</div>
							<?php
							} // Close foreach
							?>

							<!-- Rating Output: Comments -->
							<div class="postbox">
								<h3 class="hndle hndle-none"><?php _e('Hiển thị đánh giá của người dùng: Trong danh sách đánh giá', 'review-comment-integration-pro-plugin'); ?></h3>

								<div class="option">
									<p class="description">
										<?php _e('Mặc định đã được ẩn đi và được xử lý format bởi code. Cần thay đổi vui lòng liên hệ code để được hỗ trợ.', 'review-comment-integration-pro-plugin'); ?>
									</p>
								</div>
								<!-- ./extra-options -->
							</div>

							<hr /><br /><br />

							<!-- Footer content -->
							<?php require($this->base->plugin->folder . '/_modules/dashboard/views/footer-content.php'); ?>
						</div>
						<!-- /normal-sortables -->
					</div>
					<!-- /post-body-content -->

					<!-- Sidebar -->
					<div id="postbox-container-1" class="postbox-container">
						<!-- Targeted Placement Options -->
						<div class="postbox targeted-placement-options">
							<h3 class="hndle hndle-none"><?php _e('Lựa chọn mục tiêu', 'review-comment-integration-pro-plugin'); ?></h3>

							<?php
							// Go through all Post Types
							$post_types = Comment_Rating_Field_Pro_Common::get_instance()->get_post_types();
							foreach ($post_types as $type => $post_type) {
							?>
								<div class="option">
									<label for="placement_options_type_<?php echo $post_type->name; ?>">
										<div class="left">
											<strong><?php echo sprintf(__('Dùng cho %s', 'review-comment-integration-pro-plugin'), $post_type->labels->name); ?></strong>
										</div>
										<div class="right">
											<input id="placement_options_type_<?php echo $post_type->name; ?>" type="checkbox" name="placementOptions[type][<?php echo $type; ?>]" value="1" <?php echo (isset($group['placementOptions']['type'][$type]) ? ' checked' : ''); ?> />
										</div>
									</label>
								</div>
							<?php
							}
							?>
						</div>

						<!-- Save -->
						<div class="postbox targeted-placement-options">
							<h3 class="hndle hndle-none"><?php _e('Xuất bản', 'review-comment-integration-pro-plugin'); ?></h3>
							<div class="inside">
								<!-- Save -->
								<div class="submit" style="padding: 0;">
									<?php wp_nonce_field('save_group', $this->base->plugin->name . '_nonce'); ?>
									<input type="submit" name="submit" value="<?php _e('Lưu thay đổi', 'review-comment-integration-pro-plugin'); ?>" class="button-primary" style="width: 100%; background: #f31d59; border: #f31d59; text-align: center" />
								</div>
							</div>
						</div>

					</div>
				</div>
			</div>
		</form>
		<!-- /form end -->
	</div><!-- ./wrap-inner -->
</div>