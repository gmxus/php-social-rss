<?php
declare(strict_types=1);


namespace SocialRss\Parser\Vk;

use SocialRss\Parser\Feed\BaseFeed;
use SocialRss\Parser\Vk\User\User;
use SocialRss\Parser\Vk\User\UserCollection;

/**
 * Class VkFeed
 * @package SocialRss\Parser\Vk
 */
class VkFeed extends BaseFeed
{
    private $users;

    /**
     * InstagramFeed constructor.
     * @param $feed
     */
    public function __construct(array $feed)
    {
        parent::__construct($feed);
        $this->users = new UserCollection();

        $this->populateUsers();
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        $feed = $this->feed;

        // Prepare user's wall
        if (isset($feed['wall'])) {
            $feed = $this->processFeed($feed);
        }

        $feedItems = $feed['items'];
        $profiles = $this->users;

        $processedFeedItems = array_map(function ($item) use ($profiles) {
            return array_merge($item, ['profiles' => $profiles]);
        }, $feedItems);

        return $processedFeedItems;
    }

    /**
     * @param $feed
     * @return array
     */
    private function processFeed(array $feed): array
    {
        $items = array_filter($feed['wall'], function ($item) {
            return is_array($item);
        });

        $processedItems = array_map(function ($item) {
            $item['type'] = $item['post_type'];
            $item['source_id'] = $item['from_id'];
            $item['post_id'] = $item['id'];

            return $item;
        }, $items);

        return array_merge($feed, ['items' => $processedItems]);
    }


    /**
     * @return array
     * @internal param $feed
     */
    public function populateUsers()
    {
        $feed = $this->feed;

        // Get groups array
        foreach ($feed['groups'] as $group) {
            $this->users->addUser(new User($group['gid'], $group['screen_name'], $group['name'], $group['photo']));
        }

        foreach ($feed['profiles'] as $profile) {
            $user = new User(
                $profile['uid'],
                $profile['screen_name'],
                "{$profile['first_name']} {$profile['last_name']}",
                $profile['photo']
            );
            $this->users->addUser($user);
        }
    }
}
