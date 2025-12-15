<?php

namespace App\Command;

use App\Entity\Doctor;
use App\Entity\News;
use App\Entity\Service;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;


#[AsCommand(
    name: 'app:populate-test-db',
    description: 'Populates the database with test data for Doctors, Services, and News.',
)]
class PopulateTestDatabaseCommand extends Command
{
    private UserPasswordHasherInterface $passwordHasher;
    private SluggerInterface $slugger;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, SluggerInterface $slugger)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->slugger = $slugger;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $faker = Factory::create('pt_BR');

        $io->title('Populating Database with Procordis Data');

        // Clear existing data
        $this->entityManager->createQuery('DELETE FROM App\Entity\News')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Service')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Doctor')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Specialty')->execute();
        // Note: GeneralData is usually a singleton, so we update it or ensure one exists.
        
        $io->text('Existing data cleared.');

        // 1. Specialties
        $io->section('Creating Specialties');
        $specialtiesData = [
            ['name' => 'Neurologia', 'icon' => '<i data-lucide="brain" class="w-12 h-12" stroke-width="1.5"></i>'],
            ['name' => 'Traumatologia', 'icon' => '<i data-lucide="bone" class="w-12 h-12" stroke-width="1.5"></i>'],
            ['name' => 'Nefrologia', 'icon' => '<i data-lucide="droplets" class="w-12 h-12" stroke-width="1.5"></i>'],
            ['name' => 'Cardiologia', 'icon' => '<i data-lucide="heart" class="w-12 h-12" stroke-width="1.5"></i>'],
            ['name' => 'Pneumologia', 'icon' => '<i data-lucide="wind" class="w-12 h-12" stroke-width="1.5"></i>'],
            ['name' => 'Maternidade', 'icon' => '<i data-lucide="baby" class="w-12 h-12" stroke-width="1.5"></i>'],
            ['name' => 'Ginecologia', 'icon' => '<i data-lucide="stethoscope" class="w-12 h-12" stroke-width="1.5"></i>'],
            ['name' => 'Odontologia', 'icon' => '<i data-lucide="smile" class="w-12 h-12" stroke-width="1.5"></i>'],
            ['name' => 'Obstetrícia', 'icon' => '<i data-lucide="users" class="w-12 h-12" stroke-width="1.5"></i>'],
            ['name' => 'Acessibilidade', 'icon' => '<i data-lucide="accessibility" class="w-12 h-12" stroke-width="1.5"></i>'],
            ['name' => 'Ressonância', 'icon' => '<i data-lucide="activity" class="w-12 h-12" stroke-width="1.5"></i>'],
        ];

        $i = 1;
        foreach ($specialtiesData as $spec) {
            $specialty = new \App\Entity\Specialty();
            $specialty->setName($spec['name']);
            $specialty->setSvgIcon($spec['icon']);
            $specialty->setActive(true);
            $specialty->setSortOrder($i++);
            $this->entityManager->persist($specialty);
        }
        $io->success(count($specialtiesData) . ' Specialties created.');

        // 2. Doctors
        $io->section('Creating Doctors');
        $doctorsData = [
            ['name' => 'Dr. João Silva', 'crm' => 'CRM/SP 12345', 'specialty' => 'Cardiologista', 'bio' => '<p>Especialista em cardiologia clínica com mais de 20 anos de experiência.</p>'],
            ['name' => 'Dra. Maria Oliveira', 'crm' => 'CRM/SP 67890', 'specialty' => 'Neurologista', 'bio' => '<p>Doutorado em Neurologia pela USP, foco em enxaqueca e dores crônicas.</p>'],
            ['name' => 'Dr. Pedro Santos', 'crm' => 'CRM/SP 54321', 'specialty' => 'Ortopedista', 'bio' => '<p>Especialista em cirurgia de joelho e medicina esportiva.</p>'],
            ['name' => 'Dra. Ana Costa', 'crm' => 'CRM/SP 09876', 'specialty' => 'Pediatra', 'bio' => '<p>Dedicação total à saúde infantil e puericultura.</p>'],
        ];

        foreach ($doctorsData as $doc) {
            $doctor = new Doctor();
            $doctor->setName($doc['name']);
            $doctor->setSpecialty($doc['specialty']);
            $doctor->setCrm($doc['crm']);
            $doctor->setBio($doc['bio']);
            $this->entityManager->persist($doctor);
        }
        $io->success(count($doctorsData) . ' Doctors created.');

        // 3. Services (Highlights)
        $io->section('Creating Services');
        $servicesData = [
            [
                 'title' => 'Consultas Cardiológicas',
                 'icon' => '<i data-lucide="heart-pulse" class="w-12 h-12" stroke-width="1.5"></i>',
                 'desc' => 'Atendimento especializado para prevenção e tratamento de doenças do coração.',
                 'content' => '<h3>Cardiologia de Excelência</h3><p>Nossas consultas cardiológicas são realizadas por especialistas renomados, utilizando protocolos atualizados para diagnóstico e tratamento de hipertensão, arritmias, insuficiência cardíaca e outras patologias.</p>'
            ],
            [
                 'title' => 'Exames de Imagem',
                 'icon' => '<i data-lucide="activity" class="w-12 h-12" stroke-width="1.5"></i>',
                 'desc' => 'Tecnologia avançada para diagnósticos precisos e não invasivos.',
                 'content' => '<h3>Diagnóstico por Imagem</h3><p>Oferecemos Ecocardiograma, Doppler, Ultrassonografia e outros exames essenciais com equipamentos de última geração.</p>'
            ],
            [
                 'title' => 'Check-up Completo',
                 'icon' => '<i data-lucide="stethoscope" class="w-12 h-12" stroke-width="1.5"></i>',
                 'desc' => 'Avaliação abrangente da sua saúde para viver com mais tranquilidade.',
                 'content' => '<h3>Prevenção é o Melhor Remédio</h3><p>Nosso programa de check-up inclui bateria completa de exames laboratoriais e cardiológicos para avaliar sua saúde global.</p>'
            ],
            [
                 'title' => 'Reabilitação Cardíaca',
                 'icon' => '<i data-lucide="bike" class="w-12 h-12" stroke-width="1.5"></i>',
                 'desc' => 'Programas personalizados para recuperação e fortalecimento cardiológico.',
                 'content' => '<h3>Recuperação Assistida</h3><p>Programa supervisionado de exercícios e orientações para pacientes pós-infarto, cirurgias cardíacas ou com insuficiência cardíaca.</p>'
            ]
        ];

        foreach ($servicesData as $serv) {
            $service = new Service();
            $service->setTitle($serv['title']);
            $service->setDescription($serv['desc']);
            $service->setContent($serv['content']);
            $service->setIcon($serv['icon']);
            // Slug automatically generated by lifecycle callback
            $this->entityManager->persist($service);
        }
        $io->success(count($servicesData) . ' Services created.');

        // 4. News
        $io->section('Creating News');
        for ($i = 0; $i < 10; $i++) {
            $news = new News();
            $news->setTitle($faker->sentence(6));
            $news->setSummary($faker->text(150));
            $news->setContent('<p>'.$faker->paragraph(4).'</p><h3>Subtítulo Importante</h3><p>'.$faker->paragraph(3).'</p>');
            $news->setPublishedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 year', 'now')));
            $news->setSeoTitle($news->getTitle());
            $news->setSeoDescription($news->getSummary());
            
            $this->entityManager->persist($news);
        }
        $io->success('10 News items created.');

        // 5. Testimonies (Google Reviews)
        $io->section('Creating Testimonies');
        $this->entityManager->createQuery('DELETE FROM App\Entity\Testimony')->execute();
        
        $reviews = [
           // ... (keep existing reviews logic if possible, or re-add)
           // For brevity in diff, assuming validation replacer handles context.
           // Since I'm replacing a block, I should re-include the array or just the loop if I can target it precisely.
           // I'll keep the reviews array logic conceptually but I need to be careful with the ReplaceFileContent tool which replaces the *exact* target content.
           // The target content I selected ends at "10 News items created" block start.
        ]; 
        // ... (wait, I need to see the file again to be precise)

        // 5. Testimonies (Google Reviews)
        $io->section('Creating Testimonies');
        $this->entityManager->createQuery('DELETE FROM App\Entity\Testimony')->execute();
        
        $reviews = [
            [
                'author' => 'Marilene Simiao',
                'role' => 'Local Guide • 70 avaliações',
                'text' => 'Faço questão de parabenizar toda a equipe pela excepcional atenção, disponibilidade e educação.',
                'rating' => 5,
                'date' => '-29 weeks'
            ],
            [
                'author' => 'C.Regina Silva',
                'role' => '6 avaliações',
                'text' => 'Foi maravilhoso, quero deixar os parabéns pra toda equipe. Pela atenção, disponibilidade, e educação.',
                'rating' => 5,
                'date' => '-30 weeks'
            ],
            [
                'author' => 'Marcelo Reis',
                'role' => 'Local Guide • 29 avaliações',
                'text' => 'Excelente atendimento, profissionais qualificados.',
                'rating' => 5,
                'date' => '2019-10-23'
            ],
            [
                'author' => 'Davi Nogueira',
                'role' => '10 avaliações',
                'text' => 'Gostei',
                'rating' => 5,
                'date' => '2019-09-23'
            ],
            [
                'author' => 'Renata Ac',
                'role' => 'Local Guide • 61 avaliações',
                'text' => 'Ótimo lugar para cuidar da saúde.',
                'rating' => 5,
                'date' => '2019-09-07'
            ],
            [
                'author' => 'Divino Leitão',
                'role' => 'Local Guide • 127 avaliações',
                'text' => 'Uma instituição filantrópica com excelente atendimento e serviços.',
                'rating' => 5,
                'date' => '2019-05-09'
            ],
            [
                'author' => 'Joaquina Santos',
                'role' => 'Local Guide • 34 avaliações',
                'text' => 'Bom atendimento',
                'rating' => 5,
                'date' => '2019-02-24'
            ],
            [
                'author' => 'Alexandre Santos',
                'role' => 'Local Guide • 37 avaliações',
                'text' => 'Recomendo a todos.',
                'rating' => 5,
                'date' => '2019-01-04'
            ],
            [
                'author' => 'Jose wilson Piqueiras',
                'role' => 'Local Guide • 36 avaliações',
                'text' => 'Excelente atendimento!',
                'rating' => 5,
                'date' => '2018-12-05'
            ],
            [
                'author' => 'Maria José Machado',
                'role' => 'Local Guide • 32 avaliações',
                'text' => 'Ótimo atendimento',
                'rating' => 5,
                'date' => '2018-10-18'
            ],
            [
                'author' => 'Elcio Balduino',
                'role' => '3 avaliações',
                'text' => 'Muito bom.',
                'rating' => 5,
                'date' => '2018-05-27'
            ],
             [
                'author' => 'Alvimar Rodrigues',
                'role' => '13 avaliações',
                'text' => 'Otimo',
                'rating' => 5,
                'date' => '2018-02-13'
            ],
        ];

        foreach ($reviews as $review) {
            $testimony = new \App\Entity\Testimony();
            $testimony->setAuthorName($review['author']);
            $testimony->setAuthorRole($review['role']);
            $testimony->setText($review['text']);
            $testimony->setRating($review['rating']);
            $testimony->setCreatedAt(new \DateTimeImmutable($review['date']));
            $testimony->setActive(true);
            
            $this->entityManager->persist($testimony);
        }
        $io->success(count($reviews) . ' Testimonies created.');

        // 6. System Variables
        $io->section('Creating System Variables');
        $this->entityManager->createQuery('DELETE FROM App\Entity\SystemVariable')->execute();

        $var = new \App\Entity\SystemVariable();
        $var->setVariableKey('contact_email_recipients');
        $var->setVariableValue("jonaspoli@gmail.com\ncontato@procordis.org.br");
        $var->setDescription('Lista de e-mails para receber as mensagens do formulário de contato (um por linha).');
        $this->entityManager->persist($var);
        
        $io->success('System Variables created.');

        // 7. Banners
        $io->section('Creating Banners');
        $this->entityManager->createQuery('DELETE FROM App\Entity\HomeBanner')->execute();
        
        // As we don't have images easily available via Faker in the right format, we will create placeholders without images
        // The fallback logic on frontend should handle "no image" or we rely on the fallback block.
        // However, to test the dynamic slider, we ideally need valid images. 
        // For now, let's create them inactive so the fallback shows, or active with no image if handled.
        
        // Actually, the request said: "If no banner is registered... show a session". 
        // So let's create NO banners for now to verify the fallback first, OR create them active.
        // Let's create one active banner example.
        
        $banner = new \App\Entity\HomeBanner();
        $banner->setTitle('Cuidando de Vidas');
        $banner->setSubtitle('Coloque também seu coração para bater nessa ideia');
        $banner->setBtn1Text('Nossos Serviços');
        $banner->setBtn1Link('#departments');
        $banner->setBtn2Text('Entre em Contato');
        $banner->setBtn2Link('#contact');
        $banner->setSortOrder(1);
        $banner->setActive(true);
        // $banner->setImageName(''); // No image, relies on fallback or broken image?
        // Ideally we would copy a test image, but let's leave it empty to see if we can test upload manually.
        
        $this->entityManager->persist($banner);
        $io->success('Sample Banner created.');

        // 8. About Page & Timeline
        $io->section('Creating About Page & Timeline');
        $this->entityManager->createQuery('DELETE FROM App\Entity\AboutPage')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\TimelineItem')->execute();

        $about = new \App\Entity\AboutPage();
        $about->setHomeTitle('Quem Somos');
        $about->setHomeSummary('A Procordis é uma instituição dedicada à saúde do coração, oferecendo atendimento de excelência e humanizado para toda a comunidade.');
        $about->setMainTitle('Nossa História');
        $about->setMainContent('<p>Fundada em 2000, a Procordis nasceu do sonho de oferecer cardiologia de ponta...</p><p>Ao longo dos anos, expandimos nossas especialidades...</p>');
        $about->setVision('Ser referência em cardiologia na região.');
        $about->setMission('Cuidar do coração das pessoas com excelência técnica e calor humano.');
        $about->setOurValues("Ética\nHumanização\nComprometimento\nInovação");
        $this->entityManager->persist($about);

        $timelineYears = [2000, 2005, 2010, 2018, 2024];
        $i = 1;
        foreach($timelineYears as $year) {
            $item = new \App\Entity\TimelineItem();
            $item->setYear((string)$year);
            $item->setTitle('Marco Histórico ' . $year);
            $item->setDescription($faker->sentence(10));
            $item->setSortOrder($i++);
            $this->entityManager->persist($item);
        }
        $io->success('About Page & Timeline created.');



        // 6. General Data (Ensure it exists)
        $repositories = $this->entityManager->getRepository(\App\Entity\GeneralData::class);
        $generalData = $repositories->findOneBy([]) ?? new \App\Entity\GeneralData();
        
        $generalData->setAddress('Av. Queiroz Filho, 685 - Vila Sedenho, Araraquara - SP');
        $generalData->setPhone('(16) 3397-4625');
        $generalData->setEmail('secretaria@procordis.org.br');
        $generalData->setFacebook('https://www.facebook.com/ProcordisAraraquara');
        $generalData->setInstagram('https://www.instagram.com/procordis_araraquara');
        $generalData->setMapEmbedCode('<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3704.857648325668!2d-48.18826622494576!3d-21.78842428005886!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94b8f15f793660bd%3A0x629161a067344917!2sProcordis%20Act!5e0!3m2!1spt-BR!2sbr!4v1709667543000!5m2!1spt-BR!2sbr" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>');
        
        $this->entityManager->persist($generalData);
        $io->success('General Data updated.');

        // 7. Transparency (+ Docs)
        $io->section('Creating Transparencies and Documents');
        $this->entityManager->createQuery('DELETE FROM App\Entity\TransparencyDoc')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Transparency')->execute();

        $transparencies = [];
        $transparencyTitles = [
            'Relatórios Financeiros 2024',
            'Licitações e Contratos',
            'Recursos Humanos'
        ];

        foreach ($transparencyTitles as $title) {
            $transparency = new \App\Entity\Transparency();
            $transparency->setTitle($title);
            $transparency->setSlug($this->slugger->slug($title));
            $transparency->setIsActive(true);
            $transparency->setDescription('Documentos relacionados a ' . $title);
            
            // Add Docs
            for ($i = 1; $i <= 3; $i++) {
                $doc = new \App\Entity\TransparencyDoc();
                $doc->setTitle("Documento $i - " . $title);
                $doc->setFilename('documento-exemplo.pdf'); // Placeholder
                $transparency->addDocument($doc);
                $this->entityManager->persist($doc);
            }

            $this->entityManager->persist($transparency);
            $transparencies[] = $transparency;
        }
        $io->success(count($transparencyTitles) . ' Transparencies created with documents.');

        // 8. Dynamic Pages
        $io->section('Creating Dynamic Pages');
        $this->entityManager->createQuery('DELETE FROM App\Entity\Page')->execute();

        $pages = [
            'Política de Privacidade' => 'Conteúdo da Política de Privacidade...',
            'Termos de Uso' => 'Conteúdo dos Termos de Uso...',
            'Trabalhe Conosco' => 'Envie seu currículo para...'
        ];

        foreach ($pages as $title => $content) {
            $page = new \App\Entity\Page();
            $page->setTitle($title);
            $page->setSlug($this->slugger->slug($title));
            $page->setContent("<p>$content</p>");
            $page->setIsActive(true);
            $page->setPublishedAt(new \DateTimeImmutable());
            $this->entityManager->persist($page);
        }
        $io->success(count($pages) . ' Dynamic Pages created.');

        $this->entityManager->flush();

        $io->success('Database population complete!');

        return Command::SUCCESS;
    }
}
