	<ul class="items">

		<?php foreach ( $latestcomments_posts as $post ): ?>
		<li class="item clear">
			<span class="titleanddate pct85"><a href="<?php echo $post->permalink; ?>" class="title"><?php echo $post->title; ?></a> <a href="#" class="date minor"><?php echo date('M j', strtotime($post->pubdate)); ?></a></span>
			<span class="comments pct15"><a href="<?php echo $post->permalink; ?>#comments" title="<?php printf(_n('%1$d comment', '%1$d comments', $post->comments->approved->comments->count), $post->comments->approved->comments->count); ?>"><?php echo $post->comments->approved->comments->count; ?></a></span>
			<ul class="commentauthors pct85 minor">
				<?php
				$comment_count= 0;
				foreach( $latestcomments[$post->id] as $comment):
					$comment_count++;
					$opa = 'opa' . (100 - $comment_count * 15);
				?>
				<li><a href="<?php echo $comment->post->permalink; ?>#comment-<?php echo $comment->id; ?>" title="<?php printf(_t('Posted at %1$s'), date('g:m a \o\n F jS, Y', strtotime($comment->date))); ?>" class="<?php echo $opa; ?>"><?php echo $comment->name; ?></a></li>
				<?php endforeach; ?>
			</ul>
		</li>
		<?php endforeach; ?>

	</ul>
