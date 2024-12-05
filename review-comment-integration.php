<?php

/**
 * Plugin Name: Review Comment Integration
 * Plugin URI: https://sevsee.vercel.app
 * Version: 4.5.2
 * Author: 7V - Nguyen Vu
 * Author URI: https://github.com/7vnguyenvu
 * Description: Adds a 5 star rating field to the comments form in WordPress.
 * License: GPL2
 */

/**
 * Review Comment Integration Class
 * 
 * @package Review Comment Integration
 * @author  7V - Nguyen Vu
 * @version 4.5.2
 */
class ReviewCommentIntegrationPlugin
{

    /**
     * Holds the class object.
     *
     * @since   2.1.1
     *
     * @var     object
     */
    public static $instance;

    /**
     * Plugin
     *
     * @since   2.1.1
     *
     * @var     object
     */
    public $plugin = '';

    /**
     * Dashboard
     *
     * @since   2.1.1
     *
     * @var     object
     */
    public $dashboard = '';

    /**
     * Constructor.
     *
     * @since   1.0.0
     */
    public function __construct()
    {

        // Plugin Details
        $this->plugin                   = new stdClass;
        $this->plugin->name             = 'review-comment-integration-plugin';
        $this->plugin->displayName      = 'Review Comment Integration';
        $this->plugin->version          = '4.5.2';
        $this->plugin->buildDate        = '2024-01-12 00:00:00';
        $this->plugin->requires         = 3.6;
        $this->plugin->tested           = '4.5.2';
        $this->plugin->folder           = plugin_dir_path(__FILE__);
        $this->plugin->url              = plugin_dir_url(__FILE__);
        $this->plugin->documentation_url = '';
        $this->plugin->support_url      = '';
        $this->plugin->upgrade_url      = '';
        $this->plugin->review_name      = 'review-comment-integration-plugin';
        $this->plugin->review_notice = sprintf(__('Thanks for using %s to collect review ratings from web site visitors!', $this->plugin->name), $this->plugin->displayName);

        // Upgrade Reasons
        $this->plugin->upgrade_reasons = array();

        // Dashboard Submodule
        if (!class_exists('WPZincDashboardWidget')) {
            require_once($this->plugin->folder . '_modules/dashboard/dashboard.php');
        }
        $this->dashboard = new WPZincDashboardWidget($this->plugin);

        // Global
        require_once($this->plugin->folder . 'includes/global/ajax.php');
        require_once($this->plugin->folder . 'includes/global/common.php');
        require_once($this->plugin->folder . 'includes/global/fields.php');
        require_once($this->plugin->folder . 'includes/global/groups.php');
        require_once($this->plugin->folder . 'includes/global/rating-input.php');
        require_once($this->plugin->folder . 'includes/global/rating-output.php');
        require_once($this->plugin->folder . 'includes/global/settings.php');
        require_once($this->plugin->folder . 'includes/global/shortcode.php');

        // Init non-static classes
        $ajax = Comment_Rating_Field_Pro_AJAX::get_instance();
        $common = Comment_Rating_Field_Pro_Common::get_instance();
        $input = Comment_Rating_Field_Pro_Rating_Input::get_instance();
        $output = Comment_Rating_Field_Pro_Rating_Output::get_instance();
        $shortcode = Comment_Rating_Field_Pro_Shortcode::get_instance();

        // Admin
        if (is_admin()) {
            require_once($this->plugin->folder . 'includes/admin/admin.php');
            require_once($this->plugin->folder . 'includes/admin/comments.php');
            require_once($this->plugin->folder . 'includes/admin/editor.php');
            require_once($this->plugin->folder . 'includes/admin/install.php');

            // Init non-static classes
            $admin = Comment_Rating_Field_Pro_Admin::get_instance();
            $admin_comments = Comment_Rating_Field_Pro_Admin_Comments::get_instance();
            $admin_editor = Comment_Rating_Field_Pro_Editor::get_instance();
            $admin_install = Comment_Rating_Field_Pro_Install::get_instance();

            // Run upgrade routines
            add_action('init', array($this, 'upgrade'));
        }
    }

    /**
     * Runs the upgrade routine once the plugin has loaded
     *
     * @since   3.5.1
     */
    public function upgrade()
    {

        // Run upgrade routine 
        Comment_Rating_Field_Pro_Install::get_instance()->upgrade();
    }

    /**
     * Returns the singleton instance of the class.
     *
     * @since   2.1.1
     *
     * @return  object Class.
     */
    public static function get_instance()
    {

        if (!isset(self::$instance) && !(self::$instance instanceof self)) {
            self::$instance = new self;
        }

        return self::$instance;
    }
}

// Initialise class
$comment_rating_field_plugin = ReviewCommentIntegrationPlugin::get_instance();

// Register activation hooks
register_activation_hook(__FILE__, array('Comment_Rating_Field_Pro_Install', 'activate'));
add_action('activate_wpmu_site', array('Comment_Rating_Field_Pro_Install', 'activate_wpmu_site'));


///////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////// CODE CUSTOM - HANDLE UI /////////////////////////////////

///////////////////////////////// COMMENT - ANALYTICS /////////////////////////////////////
// Loại bỏ tiêu đề cũ của comments-title trong Flatsome
function remove_comments_title_script()
{ ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function removeCommentsTitle() {
                document.querySelector("#comments > h3.comments-title").remove();
            }
            removeCommentsTitle();
        });
    </script>
    <?php }
add_action('wp_footer', 'remove_comments_title_script');

// Thêm tiêu đề mới
add_action('flatsome_before_comments', 'add_custom_comments_title_and_review', 10);
function add_custom_comments_title_and_review()
{
    if (have_comments()) {
    ?>
        <!-- Tiêu đề mới -->
        <h2 class="comments-title">
            <?php printf(esc_html__('Đánh giá cho %s', 'flatsome'), '<span>' . get_the_title() . '</span>'); ?>
        </h2>

        <!-- Review Box -->
        <div class="review-box-average">
            <div class="average-star">
                <span class="average-star-number">5</span>
                <i class="icon-star"></i>
            </div>
            <div class="average-view-list">
                <?php for ($i = 5; $i >= 1; $i--) : ?>
                    <div class="view-list-row">
                        <div>
                            <span><?php echo $i; ?></span>
                            <i class="icon-star"></i>
                        </div>
                        <div class="progress progress-percent-star-<?php echo $i; ?>" style="--value: 0%"></div>
                        <div>
                            <span class="amount-rating-<?php echo $i; ?>">0</span>
                            <span>Đánh giá</span>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
            <div class="average-button">
                <p>Hãy cho tôi biết ý kiến của bạn?</p>
                <a href="#respond" rel="nofollow">
                    <button>Viết đánh giá</button>
                </a>
            </div>
        </div>

    <?php
    }
}
function fill_value_progress_star_percent()
{ ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Lấy tất cả các sao từ danh sách comment
            const starGroups = document.querySelectorAll('.comment-list .crfp-rating');
            const ratings = Array.from(starGroups).map(el => {
                const match = el.className.match(/crfp-rating-(\d+)/);
                return match ? parseInt(match[1], 10) : null; // Trả về số hoặc null nếu không tìm thấy
            });
            // Đếm số lượng mỗi giá trị từ 1 đến 5
            const ratingCounts = [5, 4, 3, 2, 1].map(rating => ratings.filter(r => r === rating).length);
            const totalRatings = ratings.length; // Tổng số đánh giá            
            const percentages = ratingCounts.map(count => (totalRatings > 0 ? (count / totalRatings) * 100 : 0)); // Tính tỷ lệ phần trăm

            // Tính giá trị trung bình của sao & Cập nhật giá trị trung bình của sao
            const averageRating = totalRatings > 0 ? (ratings.reduce((sum, rating) => sum + rating, 0) / totalRatings).toFixed(1) : 0;
            const averageStarNumberElement = document.querySelector('.average-star-number');
            if (averageStarNumberElement) {
                averageStarNumberElement.textContent = averageRating; // Cập nhật điểm trung bình
            }

            // Cập nhật giá trị cho từng progress bar
            percentages.forEach((percent, index) => {
                const starValue = 5 - index; // Giá trị sao (từ 5 đến 1)
                const progressBar = document.querySelector(`.progress-percent-star-${starValue}`);
                const amountRating = document.querySelector(`.amount-rating-${starValue}`);

                // Cập nhật giá trị tiến trình
                if (progressBar) {
                    progressBar.style.setProperty('--value', `${percent}%`);
                }

                // Cập nhật số lượng đánh giá
                if (amountRating) {
                    amountRating.textContent = ratingCounts[index]; // Cập nhật số lượng đánh giá tương ứng
                }
            });
        });
    </script>
<?php }
add_action('wp_footer', 'fill_value_progress_star_percent');

///////////////////////////////// COMMENT - SUBMIT ////////////////////////////////////////
function custom_comment_redirect($location)
{
    $location = remove_query_arg(['unapproved', 'moderation-hash'], $location);
    $location = strtok($location, '#');
    // Thêm thông báo
    return add_query_arg('comment_posted', '', $location);
}
add_filter('comment_post_redirect', 'custom_comment_redirect', 10, 1);

function display_comment_posted_notice()
{
    if (isset($_GET['comment_posted'])) {
        echo '<div id="comment-posted-notice" style="
           position: fixed; 
           bottom: 20px; 
           left: 50%; 
           transform: translateX(-50%); 
           background-color: #4CAF50; 
           color: white; 
           padding: 8px 20px; 
           border-radius: 5px; 
           box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
           z-index: 9999; 
           text-align: center; 
           max-width: 80%; 
           width: auto;">
           Bình luận của bạn đã được gửi thành công (Ẩn sau <span id="countdown">5</span> giây)
       </div>';
        echo '<script>
           var countdown = 5; // Số giây đếm ngược
           var countdownElement = document.getElementById("countdown");

           // Cập nhật đếm ngược mỗi giây
           var interval = setInterval(function() {
               countdown--;
               countdownElement.textContent = countdown;
               if (countdown <= 0) {
                   clearInterval(interval); // Dừng đếm ngược khi thời gian hết
                   var notice = document.getElementById("comment-posted-notice");
                   if (notice) {
                       notice.remove(); // Xóa phần tử khỏi DOM
                   }
               }
           }, 1000); // Cập nhật mỗi giây
       </script>';
    }
}
add_action('wp_footer', 'display_comment_posted_notice');

function add_noindex_meta_for_comment_posted()
{
    if (isset($_GET['comment_posted'])) {
        echo '<meta name="robots" content="noindex, nofollow">';
    }
}
add_action('wp_head', 'add_noindex_meta_for_comment_posted');

///////////////////////////////// COMMENT - FIELDS ////////////////////////////////////////
// Thay thế bằng trường khác, ví dụ trường điện thoại
function replace_comment_website_with_phone($fields)
{
    // Xóa trường website
    if (isset($fields['url'])) {
        unset($fields['url']);
    }

    // Lưu lại các trường mặc định để sắp xếp lại
    $author = '<p class="comment-form-author">' .
        '<label for="author">' . __('Tên', 'your-theme') . ' <span class="required">*</span></label> ' .
        '<input id="author" name="author" type="text" value="' . esc_attr(wp_get_current_commenter()['comment_author']) . '" size="30" maxlength="245" required="required" /></p>';
    $email = $fields['email'];

    // Xóa các trường mặc định
    unset($fields['author']);
    unset($fields['email']);

    // Tạo trường email không bắt buộc
    $email = '<p class="comment-form-email">' .
        '<label for="email">' . __('Email', 'your-theme') . '</label> ' .
        '<input id="email" name="email" type="email" size="30"/></p>';

    // Tạo trường phone
    $phone = '<p class="comment-form-phone">' .
        '<label for="phone">' . __('Số điện thoại', 'your-theme') . ' <span class="required">*</span></label> ' .
        '<input id="phone" name="phone" type="tel" size="30" required="required" />' .
        '</p>';

    // Thêm lại các trường theo thứ tự mong muốn
    $fields['author'] = $author;     // Tên (đầu tiên)
    $fields['phone'] = $phone;       // Số điện thoại (thứ hai)
    $fields['email'] = $email;       // Email (thứ ba)

    return $fields;
}
add_filter('comment_form_default_fields', 'replace_comment_website_with_phone');


// Tùy chỉnh form defaults
function custom_comment_form_defaults($defaults)
{
    $defaults['title_reply'] = 'Để lại đánh giá';
    $defaults['label_submit'] = 'Gửi đánh giá';

    return $defaults;
}
add_filter('comment_form_defaults', 'custom_comment_form_defaults');

// Thêm custom scripts để kiểm tra các trường bắt buộc trước khi tắt validation
function add_custom_comment_scripts()
{ ?>
    <script>
        jQuery(document).ready(function($) {
            $('#commentform').removeAttr('novalidate');

            // Xử lý khi form có thay đổi
            $('#commentform').on('input', function() {
                // Kiểm tra giá trị các trường bắt buộc
                var phone = $('#phone').val().trim();
                var author = $('#author').val().trim();

                if (phone && author) {
                    $('#commentform').attr('novalidate', true);
                }
            });
        });
    </script>
<?php }
add_action('wp_footer', 'add_custom_comment_scripts');

// Lưu số điện thoại khi comment được gửi
function save_comment_phone($comment_id)
{
    if (isset($_POST['phone'])) {
        $phone = sanitize_text_field($_POST['phone']);
        add_comment_meta($comment_id, 'phone', $phone);
    }
}
add_action('comment_post', 'save_comment_phone');

// Thêm cột Phone trong trang Comments của Admin
function add_comment_phone_column($columns)
{
    $columns['phone'] = __('Số điện thoại', 'your-theme');
    return $columns;
}
add_filter('manage_edit-comments_columns', 'add_comment_phone_column');

// Hiển thị số điện thoại trong cột mới
function display_comment_phone_column($column, $comment_id)
{
    if ('phone' === $column) {
        $phone = get_comment_meta($comment_id, 'phone', true);
        echo esc_html($phone);
    }
}
add_action('manage_comments_custom_column', 'display_comment_phone_column', 10, 2);

// Thêm trường Phone vào khung Edit Comment
function add_comment_phone_meta_box()
{
    add_meta_box(
        'comment-phone',
        __('Số điện thoại', 'your-theme'),
        'display_comment_phone_meta_box',
        'comment',
        'normal'
    );
}
add_action('add_meta_boxes_comment', 'add_comment_phone_meta_box');

// Hiển thị form edit phone trong meta box
function display_comment_phone_meta_box($comment)
{
    $phone = get_comment_meta($comment->comment_ID, 'phone', true);
    wp_nonce_field('update_comment_phone', 'comment_phone_nonce');
?>
    <table class="form-table">
        <tbody>
            <tr>
                <td>
                    <input type="tel" name="comment_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text">
                </td>
            </tr>
        </tbody>
    </table>
<?php
}

// Lưu số điện thoại khi update comment trong admin
function save_comment_phone_meta_box($comment_id)
{
    if (
        !isset($_POST['comment_phone_nonce']) ||
        !wp_verify_nonce($_POST['comment_phone_nonce'], 'update_comment_phone')
    ) {
        return;
    }

    if (isset($_POST['comment_phone'])) {
        $phone = sanitize_text_field($_POST['comment_phone']);
        update_comment_meta($comment_id, 'phone', $phone);
    }
}
add_action('edit_comment', 'save_comment_phone_meta_box');

// Thêm phone vào REST API nếu cần
function add_comment_phone_to_rest_api()
{
    register_rest_field('comment', 'phone', array(
        'get_callback' => function ($comment_arr) {
            return get_comment_meta($comment_arr['id'], 'phone', true);
        },
        'update_callback' => function ($value, $comment) {
            update_comment_meta($comment->comment_ID, 'phone', $value);
        },
        'schema' => array(
            'description' => __('Số điện thoại người comment', 'your-theme'),
            'type' => 'string'
        ),
    ));
}
add_action('rest_api_init', 'add_comment_phone_to_rest_api');

// Tùy chỉnh style cho form (tùy chọn)
function add_comment_form_styles()
{
?>
    <style>
        .comment-form-author,
        .comment-form-phone,
        .comment-form-email {
            flex: 1
        }

        .required {
            color: #f00;
        }
    </style>
<?php
}
add_action('wp_head', 'add_comment_form_styles');
