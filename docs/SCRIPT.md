# Doctrine. Дополнительные возможности

## Готовим проект

1. Запускаем контейнеры командой `docker-compose up -d`
2. Входим в контейнер командой `docker exec -it php sh`. Дальнейшие команды будем выполнять из контейнера
3. Устанавливаем зависимости командой `composer install`
4. Выполняем миграции комадной `php bin/console doctrine:migrations:migrate`

## Проверяем работоспособность приложения

1. Выполняем запрос Add user из Postman-коллекции, получаем успешный ответ.
2. Выполняем запрос Get user list из Postman-коллекции, видим добавленную запись
3. Выполняем запрос Update user из Postman-коллекции, получаем успешный ответ.
4. Ещё раз выполняем запрос Get user list из Postman-коллекции, видим, что обновились значения полей login и type.

## Добавляем фильтр по полю type

1. Добавляем маркерный интерфейс `App\Entity\TypeAwareInterface`
    ```php
    <?php
   
    namespace App\Entity;
   
    interface TypeAwareInterface
    {
    }
    ```
2. В классе `App\Entity\User` имплементируем интерфейс
3. Добавляем класс `App\Doctrine\TypeFilter`
    ```php
    <?php
    
    namespace App\Doctrine;
    
    use App\Entity\TypeAwareInterface;
    use Doctrine\ORM\Mapping\ClassMetadata;
    use Doctrine\ORM\Query\Filter\SQLFilter;
    
    class TypeFilter extends SQLFilter
    {
        public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
        {
            if (!$targetEntity->reflClass->implementsInterface(TypeAwareInterface::class)) {
                return '';
            }
    
            return $targetTableAlias.".type = 'student'";
        }
    }
    ```
4. В файле `config/packages/doctrine.yaml` добавляем в секцию `doctrine.orm` подсекцию `filters`
    ```yaml
    filters:
        type_filter:
            class: App\Doctrine\TypeFilter
            enabled: true
    ```
5. Выполняем запрос Get user list из Postman-коллекции, видим пустой ответ
6. Выполняем запрос Update user с параметром `type = student`, видим ошибку
7. Исправляем вручную в БД значение поля `type` на `student` 
8. Выполняем запрос Get user list из Postman-коллекции, видим найденную запись

## Параметризуем фильтр

1. Исправляем класс `App\Doctrine\TypeFilter`
    ```php
    <?php
    
    namespace App\Doctrine;
    
    use App\Entity\TypeAwareInterface;
    use Doctrine\ORM\Mapping\ClassMetadata;
    use Doctrine\ORM\Query\Filter\SQLFilter;
    
    class TypeFilter extends SQLFilter
    {
        public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
        {
            if (!$targetEntity->reflClass->implementsInterface(TypeAwareInterface::class)) {
                return '';
            }
    
            return $targetTableAlias.'.type = '.$this->getParameter('type');
        }
    }
    ```
2. В файле `config/packages/doctrine.yaml` в секцию `doctrine.orm.filters.type_filter` добавляем подсекцию `parameters`
    ```yaml
    parameters:
        type: teacher
    ```
3. Выполняем запрос Get user list из Postman-коллекции, видим пустой ответ
4. В файле `config/packages/doctrine.yaml` в секции `doctrine.orm.filters.type_filter.parameters` исправляем значение
поля `type` на `student`
5. Выполняем запрос Get user list из Postman-коллекции, видим найденную запись

## Настраиваем параметризацию и отключение фильтра в коде

1. Добавляем класс `App\Symfony\KernelRequestSubscriber`
    ```php
    <?php
    
    namespace App\Symfony;
    
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Component\EventDispatcher\EventSubscriberInterface;
    use Symfony\Component\HttpKernel\Event\RequestEvent;
    
    class KernelRequestSubscriber implements EventSubscriberInterface
    {
        public function __construct(private readonly EntityManagerInterface $entityManager)
        {
        }
    
        public static function getSubscribedEvents()
        {
            return [
                RequestEvent::class => 'onKernelRequest'
            ];
        }
    
        public function onKernelRequest(RequestEvent $event): void
        {
            $type = $event->getRequest()->query->get('type') ?? $event->getRequest()->request->get('type');
            if ($type !== null) {
                $filter = $this->entityManager->getFilters()->getFilter('type_filter');
                $filter->setParameter('type', $type);
            } else {
                $this->entityManager->getFilters()->disable('type');
            }
        }
    }
    ```
2. В файле `config/packages/doctrine.yaml` в секции `doctrine.orm.filters.type_filter.parameters` исправляем значение
поля `type` на `teacher`
3. Выполняем запрос Get user list из Postman-коллекции без параметра `type`, видим пустой ответ.
4. В файле `config/packages/doctrine.yaml` в секции `doctrine.orm.filters.type_filter` исправляем значение поля
`enabled` на `false`
5. Выполняем запрос Get user list из Postman-коллекции без параметра `type`, видим ошибку.
6. Исправляем класс `App\Symfony\KernelRequestSubscriber`
    ```php
    <?php
    
    namespace App\Symfony;
    
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Component\EventDispatcher\EventSubscriberInterface;
    use Symfony\Component\HttpKernel\Event\RequestEvent;
    
    class KernelRequestSubscriber implements EventSubscriberInterface
    {
        public function __construct(private readonly EntityManagerInterface $entityManager)
        {
        }
    
        public static function getSubscribedEvents()
        {
            return [
                RequestEvent::class => 'onKernelRequest'
            ];
        }
    
        public function onKernelRequest(RequestEvent $event): void
        {
            $type = $event->getRequest()->query->get('type') ?? $event->getRequest()->request->get('type');
            $filters = $this->entityManager->getFilters();
            if ($type !== null) {
                $filter = $filters->getFilter('type_filter');
                $filter->setParameter('type', $type);
            } else {
                if ($filters->isEnabled('type_filter')) {
                    $filters->disable('type_filter');
                }
            }
        }
    }
    ```
7. Выполняем запрос Get user list из Postman-коллекции без параметра `type`, видим найденную сущность.
8. Выполняем запрос Get user list из Postman-коллекции с параметром `type = teacher`, видим ошибку.
9. Исправляем класс `App\Symfony\KernelRequestSubscriber`
    ```php
    <?php
    
    namespace App\Symfony;
    
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Component\EventDispatcher\EventSubscriberInterface;
    use Symfony\Component\HttpKernel\Event\RequestEvent;
    
    class KernelRequestSubscriber implements EventSubscriberInterface
    {
        public function __construct(private readonly EntityManagerInterface $entityManager)
        {
        }
    
        public static function getSubscribedEvents()
        {
            return [
                RequestEvent::class => 'onKernelRequest'
            ];
        }
    
        public function onKernelRequest(RequestEvent $event): void
        {
            $type = $event->getRequest()->query->get('type') ?? $event->getRequest()->request->get('type');
            $filters = $this->entityManager->getFilters();
            if ($type !== null) {
                $filter = $filters->enable('type_filter');
                $filter->setParameter('type', $type);
            } else {
                if ($filters->isEnabled('type_filter')) {
                    $filters->disable('type_filter');
                }
            }
        }
    }
    ```
10. Выполняем запрос Get user list из Postman-коллекции с параметром `type = teacher`, видим пустой ответ.
11. Выполняем запрос Get user list из Postman-коллекции с параметром `type = student`, видим найденную сущность.

## Добавляем кастомный тип

1. Добавляем класс `App\Entity\FieldTypes\UserType`
    ```php
    <?php
    
    namespace App\Entity\FieldTypes;
    
    use RuntimeException;
    
    class UserType
    {
        private const TEACHER = 'teacher';
        private const STUDENT = 'student';
        private const ALLOWED_VALUES = [self::TEACHER, self::STUDENT];
        
        private function __construct(private readonly string $value)
        {
        }
        
        public static function fromString(string $value): self
        {
            if (!in_array($value, self::ALLOWED_VALUES, true)) {
                throw new RuntimeException('Invalid user type value');
            }
            
            return new self($value);
        }
    
        public function getValue(): string
        {
            return $this->value;
        }
    }
    ```
2. Добавляем класс `App\Doctrine\Types\UserTypeType`
    ```php
    <?php
    
    namespace App\Doctrine\Types;
    
    use App\Entity\FieldTypes\UserType;
    use Doctrine\DBAL\Platforms\AbstractPlatform;
    use Doctrine\DBAL\Types\ConversionException;
    use Doctrine\DBAL\Types\Type;
    use RuntimeException;
    
    class UserTypeType extends Type
    {
        public function convertToPHPValue($value, AbstractPlatform $platform): ?UserType
        {
            if ($value === null) {
                return null;
            }
    
            if (is_string($value)) {
                try {
                    return UserType::fromString($value);
                } catch (RuntimeException) {
                }
            }
    
            throw ConversionException::conversionFailed($value, $this->getName());
        }
    
        public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
        {
            if ($value === null) {
                return null;
            }
    
            if ($value instanceof UserType) {
                return $value->getValue();
            }
    
            throw ConversionException::conversionFailed($value, $this->getName());
        }
    
        public function requiresSQLCommentHint(AbstractPlatform $platform): bool
        {
            return true;
        }
    
        public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
        {
            return $platform->getStringTypeDeclarationSQL($column);
        }
    
        public function getName()
        {
            return 'userType';
        }
    }
    ```
3. В файле `config/packages/doctrine.yaml` добавляем в секцию `doctrine.dbal` подсекцию `types`
    ```yaml
    types:
        userType: App\Doctrine\Types\UserTypeType
    ```
4. Исправляем класс `App\Entity\User`
    ```php
    <?php
    
    namespace App\Entity;
    
    use App\Entity\FieldTypes\UserType;
    use DateTime;
    use Doctrine\ORM\Mapping as ORM;
    
    #[ORM\Table(name: '`user`')]
    #[ORM\Entity]
    class User implements TypeAwareInterface
    {
        #[ORM\Column(name: 'id', type: 'bigint', unique: true)]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'IDENTITY')]
        private ?int $id = null;
    
        #[ORM\Column(type: 'string', length: 32, nullable: false)]
        private string $login;
    
        #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
        private DateTime $createdAt;
    
        #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: false)]
        private DateTime $updatedAt;
    
        #[ORM\Column(type: 'userType', length: 10, nullable: false)]
        private UserType $type;
    
        public function getId(): int
        {
            return $this->id;
        }
    
        public function setId(int $id): void
        {
            $this->id = $id;
        }
    
        public function getLogin(): string
        {
            return $this->login;
        }
    
        public function setLogin(string $login): void
        {
            $this->login = $login;
        }
    
        public function getCreatedAt(): DateTime {
            return $this->createdAt;
        }
    
        public function setCreatedAt(): void {
            $this->createdAt = new DateTime();
        }
    
        public function getUpdatedAt(): DateTime {
            return $this->updatedAt;
        }
    
        public function setUpdatedAt(): void {
            $this->updatedAt = new DateTime();
        }
    
        public function getType(): UserType
        {
            return $this->type;
        }
    
        public function setType(UserType $type): void
        {
            $this->type = $type;
        }
    
        public function toArray(): array
        {
            return [
                'id' => $this->id,
                'login' => $this->login,
                'type' => $this->type->getValue(),
                'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
                'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
            ];
        }
    }
    ```
5. Исправляем класс `App\Manager\UserManager`
    ```php
    <?php
    
    namespace App\Manager;
    
    use App\Entity\FieldTypes\UserType;
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
            $user = new User();
            $user->setLogin($login);
            $user->setType(UserType::fromString($type));
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
            $user->setType(UserType::fromString($type));
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
    }
    ```
6. Выполняем команду `php bin/console doctrine:migrations:diff`, видим новую миграцию
7. Применяем миграцию командой `php bin/console doctrine:migrations:migrate`
8. Выполняем запрос Get user list из Postman-коллекции с параметром `type = student`, видим добавленную запись
9. Выполняем запрос Get user list из Postman-коллекции с параметром `type = other`, видим пустой ответ
10. Выполняем запрос Add user из Postman-коллекции с параметром `type = student`, видим успешный ответ
11. Выполняем запрос Add user из Postman-коллекции с параметром `type = other`, видим ошибку

## Заменяем кастомный тип на перечисление

1. Добавляем перечисление `App\Entity\FiedlTypes\UserTypeEnum`
    ```php
    <?php
    
    namespace App\Entity\FieldTypes;
    
    enum UserTypeEnum: string
    {
        case Teacher = 'teacher';
        case Student = 'student';
    }
    ```
2. Исправляем класс `App\Entity\User`
    ```php
    <?php
    
    namespace App\Entity;
    
    use DateTime;
    use Doctrine\ORM\Mapping as ORM;
    use App\Entity\FieldTypes\UserTypeEnum;
    
    #[ORM\Table(name: '`user`')]
    #[ORM\Entity]
    class User implements TypeAwareInterface
    {
        #[ORM\Column(name: 'id', type: 'bigint', unique: true)]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'IDENTITY')]
        private ?int $id = null;
    
        #[ORM\Column(type: 'string', length: 32, nullable: false)]
        private string $login;
    
        #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
        private DateTime $createdAt;
    
        #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: false)]
        private DateTime $updatedAt;
    
        #[ORM\Column(type: 'string', length: 10, nullable: false, enumType: UserTypeEnum::class)]
        private UserTypeEnum $type;
    
        public function getId(): int
        {
            return $this->id;
        }
    
        public function setId(int $id): void
        {
            $this->id = $id;
        }
    
        public function getLogin(): string
        {
            return $this->login;
        }
    
        public function setLogin(string $login): void
        {
            $this->login = $login;
        }
    
        public function getCreatedAt(): DateTime {
            return $this->createdAt;
        }
    
        public function setCreatedAt(): void {
            $this->createdAt = new DateTime();
        }
    
        public function getUpdatedAt(): DateTime {
            return $this->updatedAt;
        }
    
        public function setUpdatedAt(): void {
            $this->updatedAt = new DateTime();
        }
    
        public function getType(): UserTypeEnum
        {
            return $this->type;
        }
    
        public function setType(UserTypeEnum $type): void
        {
            $this->type = $type;
        }
    
        public function toArray(): array
        {
            return [
                'id' => $this->id,
                'login' => $this->login,
                'type' => $this->type->value,
                'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
                'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
            ];
        }
    }
    ```
3. Исправляем класс `App\Manager\UserManager`
    ```php
    <?php
    
    namespace App\Manager;
    
    use App\Entity\FieldTypes\UserTypeEnum;
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
            $user = new User();
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
    }
    ```
4. Выполняем команду `php bin/console doctrine:migrations:diff`, видим новую миграцию
5. Применяем миграцию командой `php bin/console doctrine:migrations:migrate`
6. Выполняем запрос Add user из Postman-коллекции с параметром `type = student`, видим успешный ответ
7. Выполняем запрос Get user list из Postman-коллекции с параметром `type = student`, видим найденные записи
8. Выполняем запрос Add user из Postman-коллекции с параметром `type = other`, видим ошибку

## Разделим пользователя на независимые сущности преподавателя и студента

1. Исправляем класс `App\Entity\User`
    ```php
    <?php
    
    namespace App\Entity;
    
    use DateTime;
    use Doctrine\ORM\Mapping as ORM;
    use App\Entity\FieldTypes\UserTypeEnum;
    
    #[ORM\MappedSuperclass]
    class User implements TypeAwareInterface
    {
        #[ORM\Column(type: 'string', length: 32, nullable: false)]
        private string $login;
    
        #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
        private DateTime $createdAt;
    
        #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: false)]
        private DateTime $updatedAt;
    
        #[ORM\Column(type: 'string', length: 10, nullable: false, enumType: UserTypeEnum::class)]
        private UserTypeEnum $type;
    
        public function getLogin(): string
        {
            return $this->login;
        }
    
        public function setLogin(string $login): void
        {
            $this->login = $login;
        }
    
        public function getCreatedAt(): DateTime {
            return $this->createdAt;
        }
    
        public function setCreatedAt(): void {
            $this->createdAt = new DateTime();
        }
    
        public function getUpdatedAt(): DateTime {
            return $this->updatedAt;
        }
    
        public function setUpdatedAt(): void {
            $this->updatedAt = new DateTime();
        }
    
        public function getType(): UserTypeEnum
        {
            return $this->type;
        }
    
        public function setType(UserTypeEnum $type): void
        {
            $this->type = $type;
        }
    
        public function toArray(): array
        {
            return [
                'login' => $this->login,
                'type' => $this->type->value,
                'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
                'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
            ];
        }
    }
    ```
2. Добавляем класс `App\Entity\Student`
    ```php
    <?php
    
    namespace App\Entity;
    
    use Doctrine\ORM\Mapping as ORM;
    
    #[ORM\Table(name: 'student')]
    #[ORM\Entity]
    class Student extends User
    {
        #[ORM\Column(name: 'id', type: 'bigint', unique: true)]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'IDENTITY')]
        private ?int $id = null;
    
        #[ORM\Column(name: 'grade_book_number', type: 'string', length: 10, unique: true)]
        private string $gradeBookNumber;
    
        public function getId(): ?int
        {
            return $this->id;
        }
    
        public function setId(?int $id): void
        {
            $this->id = $id;
        }
    
        public function getGradeBookNumber(): string
        {
            return $this->gradeBookNumber;
        }
    
        public function setGradeBookNumber(string $gradeBookNumber): void
        {
            $this->gradeBookNumber = $gradeBookNumber;
        }

        public function toArray(): array
        {
            return array_merge(
                [
                    'id' => $this->id,
                    'grade_book_number' => $this->gradeBookNumber,
                ],
                parent::toArray(),
            );
         }
    }
    ```
3. Добавляем класс `App\Entity\Teacher`
    ```php
    <?php
    
    namespace App\Entity;
    
    use Doctrine\ORM\Mapping as ORM;
    
    #[ORM\Table(name: 'teacher')]
    #[ORM\Entity]
    class Teacher extends User
    {
        #[ORM\Column(name: 'id', type: 'bigint', unique: true)]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'IDENTITY')]
        private ?int $id = null;
    
        #[ORM\Column(name: 'subject', type: 'string', length: 60, unique: true)]
        private string $subject;
    
        public function getId(): ?int
        {
            return $this->id;
        }
    
        public function setId(?int $id): void
        {
            $this->id = $id;
        }
    
        public function getSubject(): string
        {
            return $this->subject;
        }
    
        public function setSubject(string $subject): void
        {
            $this->subject = $subject;
        }
    
        public function toArray(): array
        {
            return array_merge(
                [
                    'id' => $this->id,
                    'subject' => $this->subject,
                ],
                parent::toArray(),
            );
        }
    }
    ```
4. Выполняем команду `php bin/console doctrine:migrations:diff`, видим новую миграцию
5. В сгенерированной миграции
   1. Исправляем метод `up`
       ```php
       public function up(Schema $schema): void
       {
           // this up() migration is auto-generated, please modify it to your needs
           $this->addSql('CREATE TABLE student (id BIGSERIAL NOT NULL, login VARCHAR(32) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, type VARCHAR(10) NOT NULL, grade_book_number VARCHAR(10) NOT NULL, PRIMARY KEY(id))');
           $this->addSql('CREATE UNIQUE INDEX UNIQ_B723AF332519DBBE ON student (grade_book_number)');
           $this->addSql('CREATE TABLE teacher (id BIGSERIAL NOT NULL, login VARCHAR(32) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, type VARCHAR(10) NOT NULL, subject VARCHAR(60) NOT NULL, PRIMARY KEY(id))');
           $this->addSql('CREATE UNIQUE INDEX UNIQ_B0F6A6D5FBCE3E7A ON teacher (subject)');
           $this->addSql("INSERT INTO student SELECT id, login, created_at, updated_at, 'student', 'ЗК' || id FROM \"user\" WHERE type = 'student'");
           $this->addSql("INSERT INTO teacher SELECT id, login, created_at, updated_at, 'teacher', 'Предмет №' || id FROM \"user\" WHERE type = 'teacher'");
       }
       ```
   2. Исправляем метод `down`
       ```php
       public function down(Schema $schema): void
       {
           // this down() migration is auto-generated, please modify it to your needs
           $this->addSql('DROP TABLE student');
           $this->addSql('DROP TABLE teacher');
       }
       ```
6. Применяем миграцию командой `php bin/console doctrine:migrations:migrate`
7. Выполняем запрос Get user list из Postman-коллекции с параметром `type = student`, видим ошибку
8. В классе `App\Manager\UserManager`
   1. Добавляем новые методы `getStudents` и `getTeachers`
       ```php
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
       ```
   2. Исправляем метод `createUser`
       ```php
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
       ```
9. В классе `App\Controller\Api\v1\UserController` исправляем метод `getUsers`
    ```php
    #[Route(path: '', methods: ['GET'])]
    public function getUsersAction(Request $request): Response
    {
        $type = $request->query->get('type');
        if ($type === 'student') {
            $users = $this->userManager->getStudents();
        } else {
            $users = $this->userManager->getTeachers();
        }
        $code = empty($users) ? Response::HTTP_NO_CONTENT : Response::HTTP_OK;

        return new JsonResponse(['users' => array_map(static fn(User $user) => $user->toArray(), $users)], $code);
    }
    ```
10. Выполняем запрос Add user из Postman-коллекции с параметром `type = student`, видим успешный ответ
12. Выполняем запрос Get user list из Postman-коллекции с параметром `type = student`, видим список студентов
13. Выполняем запрос Add teacher из Postman-коллекции с параметром `type = student`, видим успешный ответ
14. Выполняем запрос Get user list из Postman-коллекции с параметром `type = teacher`, видим список преподавателей

## Делаем user общей таблицей для преподавателя и студента

1. Исправляем класс `App\Entity\User`
    ```php
    <?php
    
    namespace App\Entity;
    
    use DateTime;
    use Doctrine\ORM\Mapping as ORM;
    
    #[ORM\Table(name: '`user`')]
    #[ORM\Entity]
    #[ORM\InheritanceType('SINGLE_TABLE')]
    #[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
    #[ORM\DiscriminatorMap(['student' => Student::class, 'teacher' => Teacher::class])]
    class User implements TypeAwareInterface
    {
        #[ORM\Column(name: 'id', type: 'bigint', unique: true)]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'IDENTITY')]
        private ?int $id = null;
    
        #[ORM\Column(type: 'string', length: 32, nullable: false)]
        private string $login;
    
        #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
        private DateTime $createdAt;
    
        #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: false)]
        private DateTime $updatedAt;
    
        public function getId(): ?int
        {
            return $this->id;
        }
    
        public function setId(?int $id): void
        {
            $this->id = $id;
        }
    
        public function getLogin(): string
        {
            return $this->login;
        }
    
        public function setLogin(string $login): void
        {
            $this->login = $login;
        }
    
        public function getCreatedAt(): DateTime {
            return $this->createdAt;
        }
    
        public function setCreatedAt(): void {
            $this->createdAt = new DateTime();
        }
    
        public function getUpdatedAt(): DateTime {
            return $this->updatedAt;
        }
    
        public function setUpdatedAt(): void {
            $this->updatedAt = new DateTime();
        }
    
        public function toArray(): array
        {
            return [
                'id' => $this->id,
                'login' => $this->login,
                'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
                'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
            ];
        }
    }
    ```
2. Исправляем класс `App\Entity\Student`
    ```php
    <?php
    
    namespace App\Entity;
    
    use Doctrine\ORM\Mapping as ORM;
    
    #[ORM\Table(name: 'student')]
    #[ORM\Entity]
    class Student extends User
    {
        #[ORM\Column(name: 'grade_book_number', type: 'string', length: 10, unique: true)]
        private string $gradeBookNumber;
    
        public function getGradeBookNumber(): string
        {
            return $this->gradeBookNumber;
        }
    
        public function setGradeBookNumber(string $gradeBookNumber): void
        {
            $this->gradeBookNumber = $gradeBookNumber;
        }
    
        public function toArray(): array
        {
            return array_merge(
                [
                    'grade_book_number' => $this->gradeBookNumber,
                ],
                parent::toArray(),
            );
        }
    }
    ```
3. Исправляем класс `App\Entity\Teacher`
    ```php
    <?php
    
    namespace App\Entity;
    
    use Doctrine\ORM\Mapping as ORM;
    
    #[ORM\Entity]
    class Teacher extends User
    {
        #[ORM\Column(name: 'subject', type: 'string', length: 60, unique: true)]
        private string $subject;
    
        public function getSubject(): string
        {
            return $this->subject;
        }
    
        public function setSubject(string $subject): void
        {
            $this->subject = $subject;
        }
    
        public function toArray(): array
        {
            return array_merge(
                [
                    'subject' => $this->subject,
                ],
                parent::toArray(),
            );
        }
    }
    ```
4. В классе `App\Manager\UserManager` исправляем метод `createUser`
    ```php
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
        $user->setCreatedAt();
        $user->setUpdatedAt();
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
    ```
5. Выполняем команду `php bin/console doctrine:migrations:diff`, видим новую миграцию
6. В сгенерированной миграции
   1. Исправляем метод `up`
       ```php
       public function up(Schema $schema): void
       {
           // this up() migration is auto-generated, please modify it to your needs
           $this->addSql('ALTER TABLE "user" ADD grade_book_number VARCHAR(10) DEFAULT NULL');
           $this->addSql('ALTER TABLE "user" ADD subject VARCHAR(60) DEFAULT NULL');
           $this->addSql('ALTER TABLE "user" ALTER type TYPE VARCHAR(255)');
           $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6492519DBBE ON "user" (grade_book_number)');
           $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649FBCE3E7A ON "user" (subject)');
           $this->addSql("UPDATE \"user\" SET grade_book_number = 'ЗК' || id WHERE type = 'student'");
           $this->addSql("UPDATE \"user\" SET subject = 'Предмет №' || id WHERE type = 'teacher'");
       }
       ```
   2. Исправляем метод `down`
       ```php
       public function down(Schema $schema): void
       {
           // this down() migration is auto-generated, please modify it to your needs
           $this->addSql('DROP INDEX UNIQ_8D93D6492519DBBE');
           $this->addSql('DROP INDEX UNIQ_8D93D649FBCE3E7A');
           $this->addSql('ALTER TABLE "user" DROP grade_book_number');
           $this->addSql('ALTER TABLE "user" DROP subject');
           $this->addSql('ALTER TABLE "user" ALTER type TYPE VARCHAR(10)');
       }
       ```
7. Применяем миграцию командой `php bin/console doctrine:migrations:migrate`
8. Выполняем запрос Add user из Postman-коллекции с параметром `type = student`, видим успешный ответ
9. Выполняем запрос Get user list из Postman-коллекции с параметром `type = student`, видим список студентов
10. Выполняем запрос Add user из Postman-коллекции с параметром `type = teacher`, видим успешный ответ
11. Выполняем запрос Get user list из Postman-коллекции с параметром `type = teacher`, видим список преподавателей

## Делаем таблицы для преподавателя и студента дополнительными для таблицы user

1. В классе `App\Entity\User` изменяем значение атрибута `ORM\InheritanceType` на `JOINED`.
2. Выполняем команду `php bin/console doctrine:migrations:diff`, видим новую миграцию
3. В сгенерированной миграции исправляем метод `up`
```php
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE student_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE teacher_id_seq CASCADE');
        $this->addSql('ALTER TABLE student DROP login');
        $this->addSql('ALTER TABLE student DROP created_at');
        $this->addSql('ALTER TABLE student DROP updated_at');
        $this->addSql('ALTER TABLE student DROP type');
        $this->addSql('ALTER TABLE student ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE student ADD CONSTRAINT FK_B723AF33BF396750 FOREIGN KEY (id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE teacher DROP login');
        $this->addSql('ALTER TABLE teacher DROP created_at');
        $this->addSql('ALTER TABLE teacher DROP updated_at');
        $this->addSql('ALTER TABLE teacher DROP type');
        $this->addSql('ALTER TABLE teacher ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE teacher ADD CONSTRAINT FK_B0F6A6D5BF396750 FOREIGN KEY (id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP INDEX uniq_8d93d649fbce3e7a');
        $this->addSql('DROP INDEX uniq_8d93d6492519dbbe');
        $this->addSql("INSERT INTO student SELECT id, grade_book_number FROM \"user\" WHERE type = 'student'");
        $this->addSql("INSERT INTO teacher SELECT id, subject FROM \"user\" WHERE type = 'teacher'");
        $this->addSql('ALTER TABLE "user" DROP grade_book_number');
        $this->addSql('ALTER TABLE "user" DROP subject');
    }
```
4. Применяем миграцию командой `php bin/console doctrine:migrations:migrate`
5. Выполняем запрос Get user list из Postman-коллекции с параметром `type = student`, видим список студентов
6. Выполняем запрос Get user list из Postman-коллекции с параметром `type = teacher`, видим список преподавателей
