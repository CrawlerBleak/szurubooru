<?php
class EditPostSourceJob extends AbstractPostEditJob
{
	const SOURCE = 'source';

	public function execute()
	{
		$post = $this->post;
		$newSource = $this->getArgument(self::SOURCE);

		$oldSource = $post->source;
		$post->setSource($newSource);

		PostModel::save($post);

		if ($oldSource != $newSource)
		{
			LogHelper::log('{user} changed source of {post} to {source}', [
				'user' => TextHelper::reprUser(Auth::getCurrentUser()),
				'post' => TextHelper::reprPost($post),
				'source' => $post->source]);
		}

		return $post;
	}

	public function requiresPrivilege()
	{
		return
		[
			Privilege::EditPostSource,
			Access::getIdentity($this->post->getUploader())
		];
	}

	public function requiresAuthentication()
	{
		return false;
	}

	public function requiresConfirmedEmail()
	{
		return false;
	}
}
