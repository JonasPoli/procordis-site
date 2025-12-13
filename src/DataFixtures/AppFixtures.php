<?php

namespace App\DataFixtures;

use App\Entity\GeneralData;
use App\Entity\News;
use App\Entity\Service;
use App\Entity\TeamMember;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('pt_BR');

        // 1. General Data
        $data = new GeneralData();
        $data->setPhone('(16) 3333-4444');
        $data->setWhatsapp('5516999999999');
        $data->setEmail('contato@procordis.org.br');
        $data->setAddress('Av. Brasil, 123 - Centro, Araraquara - SP');
        $manager->persist($data);

        // 2. Services (10 items)
        for ($i = 0; $i < 10; $i++) {
            $service = new Service();
            $service->setTitle($faker->unique()->sentence(3));
            $service->setSlug($faker->slug());
            $service->setDescription($faker->paragraph(3));
            $service->setContent($faker->randomHtml());
            $service->setIcon('fas fa-heartbeat');
            // Service does not have setIsActive
            $manager->persist($service);
        }

        // 3. Doctors (10 items) instead of TeamMember
        for ($i = 0; $i < 10; $i++) {
            $doctor = new \App\Entity\Doctor(); // Use fully qualified or alias
            $doctor->setName($faker->name());
            $doctor->setSpecialty('Cardiologista'); // Check if this field exists or if it's 'role'
            $doctor->setCrm($faker->numerify('CRM/SP #####'));
            $doctor->setBio($faker->paragraph());
            $manager->persist($doctor);
        }

        // 4. News (120 items to test pagination)
        for ($i = 0; $i < 120; $i++) {
            $news = new News();
            $news->setTitle($faker->sentence(6));
            $news->setSlug($faker->slug());
            $news->setSummary($faker->text(150));
            $news->setContent($faker->randomHtml(2, 5));
            $news->setPublishedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 year', 'now')));

            // SEO fields
            $news->setSeoTitle($news->getTitle());
            $news->setSeoDescription($news->getSummary());

            $manager->persist($news);
        }

        $manager->flush();
    }
}
