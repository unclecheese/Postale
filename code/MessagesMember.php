<?php
/**
 * Decorates the Member object to have a relationship to threads.
 * Stores "Deleted" and "IsRead" booleans on the join table to 
 * keep track of how each given user has acted on a given thread.
 * Note: This module works best (presentationally) if users have 
 * avatars, so they're added in automatically, but do not have to 
 * be used
 *
 * @package Postale
 */
class MessagesMember extends DataObjectDecorator {
	
	/**
	 * Update the database fields
	 * @return array
	 */
	public function extraStatics() {
		return array (
			'many_many' => array (
				'Threads' => 'Thread'
			),
			'has_one' => array (
				'Avatar' => 'Image'
			),
			'many_many_extraFields' => array (
				'Threads' => array (
					'Deleted' => 'Boolean',
					'IsRead' => 'Boolean'
				)
			)
		);
	}
	
	/**
	 * Update the CMS fields to include an upload for Avatar
	 * @param FieldSet $fields The reference to the fieldset object
	 */
	public function updateCMSFields(FieldSet &$fields) {
		$fields->addFieldToTab('Root.Avatar',new ImageField("Avatar", "Upload avatar."));
	}
	
	/**
	 * Gets the short label for a member based on {@link MessagesPage::$member_short_label_field}
	 * @return string
	 */
	public function ShortLabel() {
		return $this->owner->__get(MessagesPage::$member_short_label_field);
	}
	
	/**
	 * Gets the full label for a member based on {@link MessagesPage::$member_full_label_field}
	 * @return string
	 */	
	public function FullLabel() {
		return $this->owner->__get(MessagesPage::$member_full_label_field);
	}
	
	/**
	 * Cleanly gets the avatar so it will fall back on a default image
	 * @return Image_Cached
	 */
	public function AvatarOrDefault() {
		if($this->owner->AvatarID)
			return $this->owner->Avatar()->CroppedImage(50,50);
		return new Image_Cached('postale/images/no_avatar.jpg');
	}
	
	/**
	 * Get the number of unread messages for this user
	 *
	 * @return int
	 */
	public function UnreadMessageCount()
	{
		if($messages = MessagesPage::get_unread_messages())
			return $messages->Count();
		return false;
	}
	
	
	
}