<?php

namespace Mentoring\User;

use Mentoring\Taxonomy\TaxonomyService;

class UserService
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $dbal;

    /**
     * @var UserHydrator
     */
    protected $hydrator;

    public function __construct($dbal, $hydrator)
    {
        $this->dbal = $dbal;
        $this->hydrator = $hydrator;
    }

    public function saveUser(User $user)
    {
        $data = $this->hydrator->extract($user);
        $data['roles'] = serialize($data['roles']);
        unset($data['mentorTags']);
        unset($data['apprenticeTags']);

        if (empty($data['id'])) {
            $this->dbal->insert('users', $data);
            $user->setId($this->dbal->lastInsertId());
        } else {
            $response = $this->dbal->update('users', $data, ['id' => $data['id']]);
        }

        $this->saveUserTags($user);

        return $user;
    }

    public function fetchMentees()
    {
        $data = $this->dbal->fetchAll('SELECT * FROM users WHERE isMentee = 1');
        $users = [];
        foreach($data as $userData) {
            $users[] = $this->hydrator->hydrate($userData, new User());
        }

        return $users;
    }

    public function fetchMentors()
    {
        $data = $this->dbal->fetchAll('SELECT * FROM users WHERE isMentor = 1');
        $users = [];
        foreach($data as $userData) {
            $users[] = $this->hydrator->hydrate($userData, new User());
        }

        return $users;
    }

    public function fetchUserByGithubUid($uid)
    {
        $user = $this->dbal->fetchAssoc('SELECT * FROM users WHERE githubUid = :githubUid', ['githubUid' => $uid]);
        if ($user) {
            $user = $this->hydrator->hydrate($user, new User());
            return $user;
        }

        throw new UserNotFoundException('Could not find user with a UID of ' . $uid);
    }

    public function createUser($data)
    {
        $data['timeCreated'] = new \DateTime();
        $data['isEnabled'] = true;
        $data['isMentee'] = false;
        $data['isMentor'] = false;

        $user = $this->hydrator->hydrate($data, new User());

        return $user;
    }

    public function deleteUser($user)
    {

    }

    public function saveUserTags(User $user)
    {
        $this->dbal->delete('userTags', ['user_id' => $user->getId()]);

        foreach($user->getMentorTags() as $mentorTag) {
            echo 'Saving '.$mentorTag->getName();
            $this->dbal->insert('userTags', ['user_id' => $user->getId(), 'term_id' => $mentorTag->getId()]);
        }

        foreach($user->getApprenticeTags() as $apprenticeTag) {
            echo 'Saving '.$apprenticeTag->getName();
            $this->dbal->insert('userTags', ['user_id' => $user->getId(), 'term_id' => $apprenticeTag->getId()]);
        }
    }
}
