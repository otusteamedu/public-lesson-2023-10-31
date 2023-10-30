<?php

namespace App\Manager;

use App\Entity\FieldTypes\UserTypeEnum;
use App\Entity\Student;
use App\Entity\Teacher;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function createUser(string $login, string $type): User
    {
        if ($type === 'student') {
            $user = new Student();
            $user->setGradeBookNumber('ЗК'.random_int(1,1000));
        } else {
            $user = new Teacher();
            $user->setSubject('Предмет №'.random_int(1,1000));
        }
        $user->setLogin($login);
        $user->setType(UserTypeEnum::from($type));
        $user->setCreatedAt();
        $user->setUpdatedAt();
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function updateUser(int $userId, string $login, string $type): ?User
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var User $user */
        $user = $userRepository->find($userId);
        if ($user === null) {
            return null;
        }
        $user->setLogin($login);
        $user->setType(UserTypeEnum::from($type));
        $user->setUpdatedAt();
        $this->entityManager->flush();

        return $user;
    }

    public function deleteUser(int $userId): bool
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var User $user */
        $user = $userRepository->find($userId);
        if ($user === null) {
            return false;
        }
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return true;
    }

    public function getUser(int $userId): ?User
    {
        $userRepository = $this->entityManager->getRepository(User::class);

        return $userRepository->find($userId);
    }

    /**
     * @return User[]
     */
    public function getUsers(): array
    {
        $userRepository = $this->entityManager->getRepository(User::class);

        return $userRepository->findAll();
    }

    /**
     * @return User[]
     */
    public function getStudents(): array
    {
        $studentRepository = $this->entityManager->getRepository(Student::class);

        return $studentRepository->findAll();
    }

    /**
     * @return User[]
     */
    public function getTeachers(): array
    {
        $studentRepository = $this->entityManager->getRepository(Teacher::class);

        return $studentRepository->findAll();
    }
}
