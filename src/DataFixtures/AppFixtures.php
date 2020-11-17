<?php

namespace App\DataFixtures;

use App\Entity\Commune;
use App\Entity\Media;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Faker;

class AppFixtures extends Fixture
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder =$passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Faker\Factory::create();

        //create a admin user
        $admin = new User();
        $admin->setEmail("admin@admin.com");
        $encoded = $this->passwordEncoder->encodePassword($admin, 'thebigadmin');
        $admin->setPassword($encoded);
        $admin->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);

        //create a admin user
        for ($i=0;$i<10;$i++) {
            $user = new User();
            $user->setEmail($faker->email);
            $encoded = $this->passwordEncoder->encodePassword($user, 'littlepooruser');
            $user->setPassword($encoded);
            $user->setRoles(['ROLE_USER']);
            $manager->persist($user);
        }

        for ($nbCommune = 0; $nbCommune < 100; $nbCommune++) {

            $commune = new Commune();
            $media = new Media();

            $cityName = $faker->city;

            $commune->setCodePostal($faker->postcode)
                ->setName($cityName)
                ->setPopulation($faker->numberBetween($min = 1000, $max = 15000));
            $commune->setCodeRegion($faker->countryCode);
            $commune->setCodeDepartement($faker->numberBetween($min = 1, $max = 95));

            $commune->setSlug($cityName);

            $media->setFormat('photo')
                ->setUrl("https://picsum.photos/720/480");

            $commune->setMedia($media);

            $manager->persist($media);
            $manager->persist($commune);
        }

        $manager->flush();
    }
}
