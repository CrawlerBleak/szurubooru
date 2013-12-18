<?php
class CommentController
{
	/**
	* @route /comments
	* @route /comments/{page}
	* @validate page [0-9]+
	*/
	public function listAction($page)
	{
		$this->context->stylesheets []= 'post-small.css';
		$this->context->stylesheets []= 'comment-list.css';
		$this->context->stylesheets []= 'comment-small.css';
		$this->context->stylesheets []= 'paginator.css';
		if ($this->context->user->hasEnabledEndlessScrolling())
			$this->context->scripts []= 'paginator-endless.js';

		$page = intval($page);
		$commentsPerPage = intval($this->config->comments->commentsPerPage);
		$this->context->subTitle = 'comments';
		PrivilegesHelper::confirmWithException(Privilege::ListComments);

		$page = max(1, $page);
		$comments = CommentSearchService::getEntities(null, $commentsPerPage, $page);
		$commentCount = CommentSearchService::getEntityCount(null, $commentsPerPage, $page);
		$pageCount = ceil($commentCount / $commentsPerPage);
		CommentModel::preloadCommenters($comments);
		CommentModel::preloadPosts($comments);
		$posts = array_map(function($comment) { return $comment->getPost(); }, $comments);
		PostModel::preloadTags($posts);

		$this->context->postGroups = true;
		$this->context->transport->paginator = new StdClass;
		$this->context->transport->paginator->page = $page;
		$this->context->transport->paginator->pageCount = $pageCount;
		$this->context->transport->paginator->entityCount = $commentCount;
		$this->context->transport->paginator->entities = $comments;
		$this->context->transport->paginator->params = func_get_args();
		$this->context->transport->comments = $comments;
	}



	/**
	* @route /post/{postId}/add-comment
	* @valdiate postId [0-9]+
	*/
	public function addAction($postId)
	{
		PrivilegesHelper::confirmWithException(Privilege::AddComment);
		if ($this->config->registration->needEmailForCommenting)
			PrivilegesHelper::confirmEmail($this->context->user);

		$post = PostModel::findById($postId);

		if (InputHelper::get('submit'))
		{
			$text = InputHelper::get('text');
			$text = CommentModel::validateText($text);

			$comment = CommentModel::spawn();
			$comment->setPost($post);
			if ($this->context->loggedIn)
				$comment->setCommenter($this->context->user);
			else
				$comment->setCommenter(null);
			$comment->commentDate = time();
			$comment->text = $text;
			if (InputHelper::get('sender') != 'preview')
			{
				CommentModel::save($comment);
				LogHelper::log('{user} commented on {post}', ['post' => TextHelper::reprPost($post->id)]);
			}
			$this->context->transport->textPreview = $comment->getText();
			StatusHelper::success();
		}
	}



	/**
	* @route /comment/{id}/delete
	* @validate id [0-9]+
	*/
	public function deleteAction($id)
	{
		$comment = CommentModel::findById($id);

		PrivilegesHelper::confirmWithException(Privilege::DeleteComment, PrivilegesHelper::getIdentitySubPrivilege($comment->getCommenter()));
		CommentModel::remove($comment);

		LogHelper::log('{user} removed comment from {post}', ['post' => TextHelper::reprPost($comment->getPost())]);
		StatusHelper::success();
	}
}
