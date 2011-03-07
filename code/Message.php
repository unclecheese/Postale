<?php

/**
 * A single message that is attached to a {@link Thread}
 * Contains a body, and an author
 *
 * @package Postale
 */
class Message extends DataObject {

	static $db = array (
		'Body' => 'Text'
	);
	
	static $has_one = array (
		'Thread' => 'Thread',
		'Author' => 'Member'
	);
		
	/**
	 * After the message is written, contact all the recipients of the message
	 * (other than the author) and send them an email notification
	 */
	public function onAfterWrite() {
		parent::onAfterWrite();
		if($thread = $this->Thread()) {
			if($members = $thread->Members("`Member`.ID != {$this->AuthorID}")) {
				if($author = $this->Author()) {
					$label = MessagesPage::$member_full_label_field;
					foreach($members as $member) {
						// Send it to everyone who hasn't deleted the thread
						if(!$thread->isDeletedForUser($member->ID)) {
							$e = new Email(MessagesPage::$from_address, $member->Email, sprintf(_t('Postale.NEWMESSAGESUBJECT','%s sent you a message'), $author->FullLabel()));
							$e->ss_template = "MessageNotification";
							$e->populateTemplate(array(
								'Member' => $member,
								'Message' => $this,
								'Base' => Director::protocolAndHost(),
								'FromName' => MessagesPage::$from_name
							));
							$e->send();
						}
					}
				}
			}
			// Mark it unread for everyone
			$thread->updateIsReadForAll(false);
			// Except the author
			$thread->updateIsReadForUser(true);
		}
	}
	
	/**
	 * Provide a link to the message, with a hash for scrollTo()
	 * @return string
	 */	
	public function Link() {
		return $this->Thread()->Link()."#message{$this->ID}";
	}
	
	/**
	 * Provide a shortened version of the body text (used for autocomplete search)
	 * Configure the length of the string in {@link MessagesPage::$summary_length}
	 * @return string
	 */
	public function Summary() {
		return strlen($this->Body) > MessagesPage::$summary_length ? substr($this->Body, 0, MessagesPage::$summary_length) : $this->Body;
	}
	
	/**
	 * Fallback method for the default rendering of {@link AutoCompleteField}
	 * @return string
	 */
	public function AutoCompleteTitle() {
		return $this->Thread()->Subject;
	}
	
	/**
	 * Fallback method for the default rendering of {@link AutoCompleteField}
	 * @return string
	 */	
	public function AutoCompleteSummary() {
		return $this->Summary();
	}
	
}