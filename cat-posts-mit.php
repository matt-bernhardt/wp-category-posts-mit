<?php
/**
 * Plugin Name: Category Posts (MIT extension)
 * Plugin URI: https://github.com/matt-bernhardt/wp-category-posts-mit
 * Description: This is a sample plugin
 * Version: 0.0.2
 * Author: Matt Bernhardt
 * Author URI: https://github.com/matt-bernhardt
 * License: GPL2
 *
 * @package Category Posts (MIT extension)
 * @author Matt Bernhardt
 * @link https://github.com/matt-bernhardt/wp-category-posts-mit
 */

/**
 * {Category Posts (MIT extension)} is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * {Category Posts (MIT extension)} is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with {Category Posts (MIT extension)}. If not, see {https://github.com/matt-bernhardt/wp-category-posts-mit/blob/master/LICENSE}.
 */

// Don't call the file directly
if ( !defined( 'ABSPATH' ) ) exit;

class CategoryPostsMIT extends CategoryPosts {

	function __construct() {
		$widget_ops = array('classname' => 'cat-post-widget', 'description' => __('List single category posts','categoryposts'));
		parent::__construct('category-posts', __('Category Posts (MIT)','categoryposts'), $widget_ops);
	}

	function widget($args, $instance) {

		extract( $args );

		// If not title, use the name of the category.
		if( !$instance["title"] ) {
			$category_info = get_category($instance["cat"]);
			$instance["title"] = $category_info->name;
		}

		$valid_sort_orders = array('date', 'title', 'comment_count', 'rand');
		if ( in_array($instance['sort_by'], $valid_sort_orders) ) {
			$sort_by = $instance['sort_by'];
			$sort_order = (bool) isset( $instance['asc_sort_order'] ) ? 'ASC' : 'DESC';
		} else {
			// by default, display latest first
			$sort_by = 'date';
			$sort_order = 'DESC';
		}
		
		// Exclude current post
		$current_post_id = get_the_ID();
		$exclude_current_post = (isset( $instance['exclude_current_post'] ) && $instance['exclude_current_post'] != -1) ? $current_post_id : "";		

		// Get array of post info.
		$args = array(
			'showposts' => $instance["num"],
			'cat' => $instance["cat"],
			'post__not_in' => array( $exclude_current_post ),
			'orderby' => $sort_by,
			'order' => $sort_order
		);
		
		if( isset( $instance['hideNoThumb'] ) ) {
			$args = array_merge( $args, array( 'meta_query' => array(
					array(
					 'key' => '_thumbnail_id',
					 'compare' => 'EXISTS' )
					)
				)	
			);
		}
		
		$cat_posts = new WP_Query( $args );
		
		if ( !isset ( $instance["hide_if_empty"] ) || $cat_posts->have_posts() ) {
			
			// Excerpt length filter
			$new_excerpt_length = create_function('$length', "return " . $instance["excerpt_length"] . ";");
			if ( $instance["excerpt_length"] > 0 )
				add_filter('excerpt_length', $new_excerpt_length);		

			echo $before_widget;

			// Widget title
			if( !isset ( $instance["hide_title"] ) ) {
				echo $before_title;
				if( isset ( $instance["title_link"] ) ) {
					echo '<a href="' . get_category_link($instance["cat"]) . '">' . $instance["title"] . '</a>';
				} else {
					echo $instance["title"];
				}
				echo $after_title;
			}

			// Post list
			echo "<ul>\n";

			while ( $cat_posts->have_posts() )
			{
				$cat_posts->the_post(); ?>
				
				<li <?php if( !isset( $instance['disable_css'] ) ) {
						echo "class=\"cat-post-item cat-post-item-mit";
							if ( is_single(get_the_title() ) ) { echo " cat-post-current"; }
						echo "\"";
					} ?> >
					
					<?php
					if( isset( $instance["thumbTop"] ) ) : 
						if ( current_theme_supports("post-thumbnails") &&
								isset( $instance["thumb"] ) &&
								has_post_thumbnail() ) : ?>
							<a <?php if( !isset( $instance['disable_css'] ) ) { echo "class=\"cat-post-thumbnail\""; } ?>
								href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
								<?php the_post_thumbnail( array($instance['thumb_w'],$instance['thumb_h'])); ?>
							</a>
					<?php endif; 
					endif; ?>					
					
					<a class="post-title <?php if( !isset( $instance['disable_css'] ) ) { echo " cat-post-title"; } ?>" 
						href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?>
					</a>

					<?php 

					if( !isset( $instance["thumbTop"] ) ) : 
						if ( current_theme_supports("post-thumbnails") &&
								isset( $instance["thumb"] ) &&
								has_post_thumbnail() ) : ?>
							<a <?php if( !isset( $instance['disable_css'] ) ) { echo "class=\"cat-post-thumbnail\""; } ?>
								href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
								<?php the_post_thumbnail( array($instance['thumb_w'],$instance['thumb_h'])); ?>
							</a>
					<?php endif;
					endif;

					if ( isset( $instance['excerpt'] ) ) : 
						the_excerpt();
					endif;
					
					if ( isset( $instance['comment_num'] ) ) : ?>
						<p class="comment-num <?php if( !isset( $instance['disable_css'] ) ) { echo "cat-post-comment-num"; } ?>">
							(<?php comments_number(); ?>)
						</p>
					<?php endif;					

					if ( isset( $instance['author'] ) ) : ?>
						<p class="post-author <?php if( !isset( $instance['disable_css'] ) ) { echo "cat-post-author"; } ?>">
							<?php the_author_posts_link(); ?>
						</p>
					<?php endif; 

					if ( isset( $instance['date'] ) ) : ?>
						<?php if ( isset( $instance['date_format'] ) && strlen( trim( $instance['date_format'] ) ) > 0 ) { $date_format = $instance['date_format']; } else { $date_format = "j M Y"; } ?>
						<p class="post-date <?php if( !isset( $instance['disable_css'] ) ) { echo "cat-post-date"; } ?>">						
						<?php if( isset ( $instance["date_link"] ) ) { ?> <a href="<?php the_permalink(); ?>"><?php } ?>
							<?php the_time($date_format); ?>
						<?php if( isset ( $instance["date_link"] ) ) { echo "</a>"; } ?>
						</p>
					<?php endif;

					?>
				</li>
				<?php
			}

			echo "</ul>\n";

			// Footer link to category page
			if( isset ( $instance["footer_link"] ) && $instance["footer_link"] ) {
				echo "<a";
					if( !isset( $instance['disable_css'] ) ) { echo " class=\"cat-post-footer-link\""; }
				echo " href=\"" . get_category_link($instance["cat"]) . "\">" . $instance["footer_link"] . "</a>";
			}

			echo $after_widget;

			remove_filter('excerpt_length', $new_excerpt_length);

			wp_reset_postdata();
			
		} // END if

	}

}

add_action( 'widgets_init', create_function('', 'return register_widget("CategoryPostsMIT");') );