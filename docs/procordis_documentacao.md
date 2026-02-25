# Documentação Técnica Completa: Sistema Procordis
## Blueprint para Reconstrução Total do Sistema

> [!CAUTION]
> **MANUAL TÉCNICO DEFINITIVO - FONTE ÚNICA DE VERDADE**
> 
> Este documento contém TODAS as informações necessárias para reconstruir o sistema completo do zero.
> Inclui: schemas de banco, código-fonte de referência, configurações completas, padrões de UI e guias passo a passo.

---

# ÍNDICE

1. **Configuração e Arquitetura**
2. **Camada de Dados (Entities & Repositories)**  
3. **Camada de Lógica (Controllers & Forms)**
4. **Camada de Apresentação (Templates & Assets)**
5. **Painel Administrativo**
6. **APIs e Integrações**
7. **Guias de Implementação**

---

# 1. CONFIGURAÇÃO E ARQUITETURA

## 1.1 Stack Tecnológica Completa

| Camada | Tecnologia | Versão | Propósito |
|--------|------------|--------|-----------|
| **Runtime** | PHP | 8.2+ | Linguagem backend |
| **Framework** | Symfony | 7.2 | Framework MVC |
| **DB** | SQLite/MySQL | - | Persistência |
| **ORM** | Doctrine | 3.x | Mapeamento objeto-relacional |
| **Templating** | Twig | 3.x | Motor de templates |
| **CSS** | TailwindCSS | 3.x | Framework utility-first |
| **JS (Interatividade)** | Alpine.js | 3.x | Framework reativo leve |
| **JS (Notificações)** | SweetAlert2 | 11.x | Popups e alertas |
| **JS (Animações)** | AOS.js | - | Scroll animations |
| **Ícones** | Lucide Icons | Latest | Biblioteca SVG |
| **Upload** | VichUploaderBundle | - | Gestão de arquivos |
| **Editor** | TinyMCE | 6.x | WYSIWYG para admin |

## 1.2 Estrutura de Diretórios

```
procordis-site/
├── assets/
│   └── app.js                    # Entry point do AssetMapper
├── config/
│   └── packages/
│       ├── vich_uploader.yaml    # Configuração de uploads
│       └── twig.yaml              # Configuração Twig
├── public/
│   ├── css/
│   │   └── built.css             # CSS compilado do Tailwind
│   ├── js/
│   │   └── simple-aos.js         # AOS library
│   ├── images/
│   │   ├── news/                 # Uploads de notícias
│   │   ├── team/                 # Fotos dos médicos
│   │   ├── banners/              # Imagens do slider
│   │   ├── about/                # Imagens Quem Somos
│   │   └── timeline/             # Imagens da linha do tempo
│   └── files/
│       └── transparency/         # PDFs de transparência
├── src/
│   ├── Command/
│   │   └── PopulateTestDatabaseCommand.php
│   ├── Controller/
│   │   ├── Admin/                # CRUDs administrativos
│   │   ├── Api/                  # Endpoints JSON
│   │   └── HomeController.php    # Controlador público
│   ├── Entity/                   # 16 entidades
│   ├── Form/                     # FormTypes
│   ├── Repository/               # Consultas customizadas
│   └── Service/
│       └── TemplateService.php   # Injeção de dados globais
├── templates/
│   ├── admin/
│   │   ├── base_admin.html.twig  # Layout admin
│   │   ├── doctor/               # CRUD templates
│   │   ├── news/
│   │   ├── service/
│   │   └── ...
│   ├── home/
│   │   ├── index.html.twig       # Homepage
│   │   └── about.html.twig       # Quem Somos
│   ├── layouts/
│   │   └── pixel_perfect.html.twig  # Base HTML
│   └── base_public.html.twig     # Layout público comum
├── tailwind.config.js
└── package.json
```

## 1.3 Configuração Tailwind (COMPLETA)

```javascript
// tailwind.config.js
/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
  ],
  theme: {
    container: {
      center: true,
      padding: "1rem",
      screens: {
        sm: "640px",
        md: "768px",
        lg: "1024px",
        xl: "1200px",
        "2xl": "1200px",
      },
    },
    extend: {
      fontFamily: {
        sans: ['Lato', 'sans-serif'],
        heading: ['Montserrat', 'sans-serif'],
        display: ['Oswald', 'sans-serif'],
      },
      colors: {
        // Sistema de Cores Médicas
        medical: {
          blue: "#0ea5e9",
          "blue-light": "#38bdf8",
          "blue-dark": "#0284c7",
          gray: "#f3f4f6",
          text: "#64748b",
          heading: "#1e293b",
        },
        topbar: {
          bg: "#0f172a",
        },
        // Cores baseadas em CSS variables
        border: "hsl(var(--border))",
        background: "hsl(var(--background))",
        foreground: "hsl(var(--foreground))",
        primary: {
          DEFAULT: "hsl(var(--primary))",
          foreground: "hsl(var(--primary-foreground))",
          light: "hsl(var(--primary-light))",
          dark: "hsl(var(--primary-dark))",
        },
        // Cores do Admin
        sidebar: {
          DEFAULT: "hsl(var(--sidebar-background))",
          foreground: "hsl(var(--sidebar-foreground))",
        },
      },
      keyframes: {
        fadeInUp: {
          from: { opacity: "0", transform: "translateY(20px)" },
          to: { opacity: "1", transform: "translateY(0)" },
        },
      },
      animation: {
        "fade-in-up": "fadeInUp 0.6s ease-out forwards",
      },
    },
  },
  plugins: [],
}
```

**CSS Variables (em `templates/layouts/pixel_perfect.html.twig` ou global CSS):**
```css
:root {
  --primary: 199 89% 48%;          /* Sky blue */
  --primary-foreground: 0 0% 100%;
  --primary-light: 199 89% 68%;
  --primary-dark: 199 89% 38%;
  --background: 0 0% 100%;
  --foreground: 222 47% 11%;
  --border: 214 32% 91%;
  --medical-blue: 199 89% 48%;
  --topbar-bg: 222 47% 11%;
}
```

## 1.4 Configuração VichUploader (COMPLETA)

```yaml
# config/packages/vich_uploader.yaml
vich_uploader:
    db_driver: orm
    
    mappings:
        news_image:
            uri_prefix: /images/news
            upload_destination: '%kernel.project_dir%/public/images/news'
            namer: Vich\UploaderBundle\Naming\SmartUniqueNamer
            
        service_image:
            uri_prefix: /images/services
            upload_destination: '%kernel.project_dir%/public/images/services'
            namer: Vich\UploaderBundle\Naming\SmartUniqueNamer
            
        doctor_image:
            uri_prefix: /images/team
            upload_destination: '%kernel.project_dir%/public/images/team'
            namer: Vich\UploaderBundle\Naming\SmartUniqueNamer
            
        transparency_file:
            uri_prefix: /files/transparency
            upload_destination: '%kernel.project_dir%/public/files/transparency'
            namer: Vich\UploaderBundle\Naming\SmartUniqueNamer
            
        timeline_image:
            uri_prefix: /images/timeline
            upload_destination: '%kernel.project_dir%/public/images/timeline'
            namer: Vich\UploaderBundle\Naming\SmartUniqueNamer
            
        banner_image:
            uri_prefix: /images/banners
            upload_destination: '%kernel.project_dir%/public/images/banners'
            namer: Vich\UploaderBundle\Naming\SmartUniqueNamer
            
        about_image:
            uri_prefix: /images/about
            upload_destination: '%kernel.project_dir%/public/images/about'
            namer: Vich\UploaderBundle\Naming\SmartUniqueNamer
```

---

# 2. CAMADA DE DADOS

## 2.1 Entidades - Schema Completo

### Doctor Entity (CÓDIGO COMPLETO)

```php
<?php
// src/Entity/Doctor.php
namespace App\Entity;

use App\Repository\DoctorRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: DoctorRepository::class)]
#[Vich\Uploadable]
class Doctor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $crm = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $specialty = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bio = null;

    #[Vich\UploadableField(mapping: 'doctor_image', fileNameProperty: 'imageName')]
    private ?File $imageFile = null;

    #[ORM\Column(nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    // Getters and setters...
    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    // ... (completar getters/setters para todos os campos)
    
    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;
        if (null !== $imageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }
}
```

### Resumo de Todas as Entidades

| Entidade | Tabela | Campos Principais | Vich Mapping |
|----------|--------|-------------------|--------------|
| **Doctor** | `doctor` | id, name, crm, specialty, bio, imageName | doctor_image |
| **Specialty** | `specialty` | id, name, svgIcon, active, sortOrder | - |
| **Service** | `service` | id, title, slug, description, content, icon | service_image |
| **News** | `news` | id, title, slug, summary, content, publishedAt, seoTitle, seoDescription | news_image |
| **Testimony** | `testimony` | id, authorName, authorRole, text, rating, active, createdAt | - |
| **HomeBanner** | `home_banner` | id, title, subtitle, btn1Text, btn1Link, btn2Text, btn2Link, active, sortOrder | banner_image |
| **AboutPage** | `about_page` | id, homeTitle, homeSummary, mainTitle, mainContent, mission, vision, ourValues | about_image |
| **TimelineItem** | `timeline_item` | id, year, title, description, sortOrder | timeline_image |
| **GeneralData** | `general_data` | id, phone, email, address, facebook, instagram, mapEmbedCode | - |
| **SystemVariable** | `system_variable` | id, variableKey (unique), variableValue, description | - |
| **NewsletterSubscriber** | `newsletter_subscriber` | id, email (unique), createdAt | - |
| **ContactMessage** | `contact_message` | id, name, email, subject, message, status, createdAt | - |
| **TransparencyDoc** | `transparency_doc` | id, title, description, fileName, category, publishedAt | transparency_file |
| **User** | `user` | id, email (unique), password, roles | - |
| **PageSeo** | `page_seo` | id, homePageTitle, homePageDescription, aboutPageTitle, ... | - |
| **GlobalTags** | `global_tags` | id, ga4, tagsGoogleAds, pixelMetaAds | - |

---

# 3. CAMADA DE LÓGICA

## 3.1 Padrão de Controller Admin (COMPLETO)

**Exemplo: DoctorController**

```php
<?php
// src/Controller/Admin/DoctorController.php
namespace App\Controller\Admin;

use App\Entity\Doctor;
use App\Form\DoctorType;
use App\Repository\DoctorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/doctor')]
#[IsGranted('ROLE_ADMIN')]
class DoctorController extends AbstractController
{
    #[Route('/', name: 'admin_doctor_index', methods: ['GET'])]
    public function index(DoctorRepository $doctorRepository): Response
    {
        return $this->render('admin/doctor/index.html.twig', [
            'doctors' => $doctorRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_doctor_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $doctor = new Doctor();
        $form = $this->createForm(DoctorType::class, $doctor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($doctor);
            $entityManager->flush();
            $this->addFlash('success', 'Médico adicionado com sucesso!');
            return $this->redirectToRoute('admin_doctor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/doctor/new.html.twig', [
            'doctor' => $doctor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_doctor_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Doctor $doctor, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DoctorType::class, $doctor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Médico atualizado com sucesso!');
            return $this->redirectToRoute('admin_doctor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/doctor/edit.html.twig', [
            'doctor' => $doctor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_doctor_delete', methods: ['POST'])]
    public function delete(Request $request, Doctor $doctor, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $doctor->getId(), $request->request->get('_token'))) {
            $entityManager->remove($doctor);
            $entityManager->flush();
            $this->addFlash('success', 'Médico excluído com sucesso!');
        }

        return $this->redirectToRoute('admin_doctor_index', [], Response::HTTP_SEE_OTHER);
    }
}
```

## 3.2 Padrão de Form Type (COMPLETO)

```php
<?php
// src/Form/DoctorType.php
namespace App\Form;

use App\Entity\Doctor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class DoctorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nome',
                'attr' => ['class' => 'form-input']
            ])
            ->add('crm', TextType::class, [
                'label' => 'CRM',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('specialty', TextType::class, [
                'label' => 'Especialidade',
                'required' => false,
                'attr' => ['class' => 'form-input']  
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Foto',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => true,
                'image_uri' => true,
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Biografia',
                'required' => false,
                'attr' => ['rows' => 4, 'class' => 'form-textarea editor-html']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Doctor::class,
        ]);
    }
}
```

## 3.3 Controller Público (HomeController)

```php
<?php
// src/Controller/HomeController.php
namespace App\Controller;

use App\Repository\AboutPageRepository;
use App\Repository\DoctorRepository;
use App\Repository\GeneralDataRepository;
use App\Repository\HomeBannerRepository;
use App\Repository\NewsRepository;
use App\Repository\ServiceRepository;
use App\Repository\SpecialtyRepository;
use App\Repository\TestimonyRepository;
use App\Repository\TimelineItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        ServiceRepository $serviceRepository,
        DoctorRepository $doctorRepository,
        NewsRepository $newsRepository,
        SpecialtyRepository $specialtyRepository,
        TestimonyRepository $testimonyRepository,
        HomeBannerRepository $bannerRepository,
        AboutPageRepository $aboutRepository,
        GeneralDataRepository $generalDataRepository
    ): Response {
        return $this->render('home/index.html.twig', [
            'services' => $serviceRepository->findBy(['active' => true], null, 8),
            'doctors' => $doctorRepository->findAll(),
            'latestNews' => $newsRepository->findBy([], ['publishedAt' => 'DESC'], 5),
            'specialties' => $specialtyRepository->findBy(['active' => true], ['sortOrder' => 'ASC']),
            'testimonies' => $testimonyRepository->findBy(['active' => true]),
            'banners' => $bannerRepository->findBy(['active' => true], ['sortOrder' => 'ASC']),
            'about' => $aboutRepository->findOneBy([]) ?? new \App\Entity\AboutPage(),
            'generalData' => $generalDataRepository->findOneBy([]) ?? new \App\Entity\GeneralData(),
        ]);
    }

    #[Route('/sobre', name: 'app_about')]
    public function about(
        AboutPageRepository $aboutPageRepository,
        TimelineItemRepository $timelineRepository,
        GeneralDataRepository $generalDataRepository
    ): Response {
        return $this->render('home/about.html.twig', [
            'about' => $aboutPageRepository->findOneBy([]) ?? new \App\Entity\AboutPage(),
            'timeline' => $timelineRepository->findBy([], ['sortOrder' => 'ASC']),
            'generalData' => $generalDataRepository->findOneBy([]) ?? new \App\Entity\GeneralData(),
        ]);
    }
}
```

## 3.4 API Controllers (COMPLETO)

```php
<?php
// src/Controller/Api/NewsletterApiController.php
namespace App\Controller\Api;

use App\Entity\NewsletterSubscriber;
use App\Repository\NewsletterSubscriberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/newsletter')]
class NewsletterApiController extends AbstractController
{
    #[Route('', name: 'api_newsletter_submit', methods: ['POST'])]
    public function submit(
        Request $request,
        EntityManagerInterface $entityManager,
        NewsletterSubscriberRepository $repository
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['email'])) {
            return $this->json(['error' => 'E-mail obrigatório.'], 400);
        }

        $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
        if (!$email) {
            return $this->json(['error' => 'E-mail inválido.'], 400);
        }

        // Check duplicate
        if ($repository->findOneBy(['email' => $email])) {
            return $this->json(['error' => 'Duplicate: Este e-mail já está cadastrado.'], 400);
        }

        $subscriber = new NewsletterSubscriber();
        $subscriber->setEmail($email);
        $subscriber->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($subscriber);
        $entityManager->flush();

        return $this->json(['success' => true]);
    }
}
```

---

# 4. CAMADA DE APRESENTAÇÃO

## 4.1 Layout Base (pixel_perfect.html.twig)

```twig
<!DOCTYPE html>
<html lang="pt-br" class="scroll-smooth">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{% block title %}Procordis{% endblock %}</title>
        
        <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 128 128%22><text y=%221.2em%22 font-size=%2296%22>❤️</text></svg>">
        
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&family=Montserrat:wght@400;500;600;700&family=Oswald:wght@400;600;700&display=swap" rel="stylesheet">
        
        <link rel="stylesheet" href="{{ asset('css/built.css') }}">
        {% block importmap %}{{ importmap('app') }}{% endblock %}
    </head>
    <body class="flex flex-col min-h-screen font-sans antialiased text-medical-text bg-white">
        {% block body %}{% endblock %}
        
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="//unpkg.com/alpinejs" defer></script>
        <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js" onload="lucide.createIcons()"></script>
        <script src="{{ asset('js/simple-aos.js') }}"></script>
        <script>AOS.init();</script>
    </body>
</html>
```

## 4.2 Base Public Layout (ESTRUTURA COMPLETA)

```twig
{# templates/base_public.html.twig #}
{% extends 'layouts/pixel_perfect.html.twig' %}

{% block body %}
    {# TopBar #}
    <div class="bg-topbar-bg text-primary-foreground py-2 text-sm hidden md:block">
      <div class="container mx-auto flex justify-between items-center">
        <div class="flex items-center gap-6">
          <div class="flex items-center gap-2">
            <i data-lucide="map-pin" class="w-4 h-4 text-primary"></i>
            <span>{{ generalData.address|default('Av. Queiroz Filho, 685') }}</span>
          </div>
        </div>
        <div class="flex items-center gap-4">
          <i data-lucide="phone" class="w-4 h-4"></i>
          <span>{{ generalData.phone|default('(16) 3397-4625') }}</span>
        </div>
      </div>
    </div>

    {# Header #}
    <header class="bg-background/95 backdrop-blur-sm sticky top-0 z-50 border-b" x-data="{ isMenuOpen: false }">
      <div class="container mx-auto py-4">
        <div class="flex items-center justify-between">
          <a href="{{ path('app_home') }}">
            <img src="{{ asset('assets/images/logo-f21ip-9.svg') }}" alt="Loga Procordis" class="h-12 w-auto"> 
          </a>

          {# Desktop Menu #}
          <nav class="hidden lg:flex items-center gap-8">
            <a href="{{ path('app_home') }}" class="{{ app.request.get('_route') == 'app_home' ? 'text-primary' : 'text-medical-text hover:text-primary' }}">Início</a>
            <a href="{{ path('app_about') }}" class="{{ app.request.get('_route') == 'app_about' ? 'text-primary' : 'text-medical-text hover:text-primary' }}">Quem Somos</a>
            
            {% if app.request.get('_route') == 'app_home' %}
              <a href="#services">Serviços</a>
              <a href="#contact">Contato</a>
            {% else %}
              <a href="{{ path('app_home') }}#services">Serviços</a>
              <a href="{{ path('app_home') }}#contact">Contato</a>
            {% endif %}
          </nav>

          {# Mobile Toggle #}
          <button class="lg:hidden p-2" @click="isMenuOpen = !isMenuOpen">
            <i x-show="!isMenuOpen" data-lucide="menu" class="w-6 h-6"></i>
            <i x-show="isMenuOpen" data-lucide="x" class="w-6 h-6" style="display: none;"></i>
          </button>
        </div>
      </div>
    </header>

    <main>
        {% block content %}{% endblock %}
    </main>

    {# Footer com Newsletter #}
    <footer class="bg-topbar-bg text-primary-foreground">
      <div class="border-b border-primary-foreground/20">
        <div class="container mx-auto py-6">
          <div class="flex items-center justify-between gap-4">
            <div class="md:w-1/2">
              <h3 class="text-xl font-bold mb-1">Fique por dentro</h3>
              <p class="text-sm opacity-70">Receba dicas de saúde</p>
            </div>
            <form class="flex md:w-1/2" x-data="{
                email: '',
                loading: false,
                async submit() {
                    this.loading = true;
                    try {
                        const res = await fetch('/api/newsletter', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ email: this.email })
                        });
                        const result = await res.json();
                        if (res.ok) {
                            Swal.fire({
                                title: 'Inscrição Confirmada!',
                                text: 'Obrigado por se inscrever.',
                                icon: 'success',
                                confirmButtonColor: '#0ea5e9'
                            });
                            this.email = '';
                        } else {
                            Swal.fire({
                                title: result.error.includes('Duplicate') ? 'Já Inscrito' : 'Erro',
                                text: result.error,
                                icon: 'info'
                            });
                        }
                    } catch(e) { alert('Erro de conexão'); }
                    finally { this.loading = false; }
                }
            }" @submit.prevent="submit">
              <input type="email" x-model="email" placeholder="Seu e-mail" required class="flex-1 px-4 py-3 rounded-l-lg">
              <button type="submit" class="px-6 py-3 bg-primary rounded-r-lg" :disabled="loading">
                <span x-show="!loading">Inscrever</span>
                <span x-show="loading">...</span>
              </button>
            </form>
          </div>
        </div>
      </div>
      {# Footer links ... #}
    </footer>
{% endblock %}
```

## 4.3 Hero Slider Implementation

```twig
{# templates/home/index.html.twig - Hero Section #}
<section class="relative min-h-[700px] overflow-hidden" 
         x-data="{ currentSlide: 0 }" 
         x-init="setInterval(() => { currentSlide = (currentSlide + 1) % {{ banners|length > 0 ? banners|length : 2 }}; }, 5000);">
  
  {% if banners|length > 0 %}
    {% for banner in banners %}
      <div class="absolute inset-0 bg-cover bg-center"
           x-show="currentSlide === {{ loop.index0 }}"
           x-transition:enter="transition ease-out duration-1000"
           x-transition:enter-start="opacity-0"
           x-transition:enter-end="opacity-100"
           x-transition:leave="transition ease-in duration-1000"
           x-transition:leave-start="opacity-100"
           x-transition:leave-end="opacity-0"
           style="background-image: url('{{ vich_uploader_asset(banner, 'imageFile') }}')">
        
        <div class="absolute inset-0 bg-gradient-to-r from-medical-heading/80 to-transparent"></div>
        
        <div class="relative container mx-auto h-full flex items-center py-32">
          <div class="max-w-xl text-white">
            <h1 class="text-6xl font-bold mb-4">{{ banner.title }}</h1>
            {% if banner.subtitle %}<p class="text-2xl mb-8">{{ banner.subtitle }}</p>{% endif %}
            
            <div class="flex gap-4">
              {% if banner.btn1Text %}
                <a href="{{ banner.btn1Link }}" class="px-6 py-3 border-2 border-white hover:bg-white hover:text-primary transition">
                  {{ banner.btn1Text }}
                </a>
              {% endif %}
            </div>
          </div>
        </div>
      </div>
    {% endfor %}
  {% else %}
    {# Fallback static content #}
    <div class="absolute inset-0 bg-cover" style="background-image: url('{{ asset('assets/images/hero.jpg') }}')">
      {# ... fallback content ... #}
    </div>
  {% endif %}

  {# Slider Dots #}
  <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex gap-2">
    {% for banner in banners %}
      <button @click="currentSlide = {{ loop.index0 }}" 
              class="w-3 h-3 rounded-full transition"
              :class="currentSlide === {{ loop.index0 }} ? 'bg-white' : 'bg-white/50'"></button>
    {% endfor %}
  </div>
</section>
```

## 4.4 Testimonials Slider

```twig
<section class="py-24 bg-primary text-white" 
         x-data="{ 
            current: 0, 
            quotes: {{ testimonies|map(t => {text: t.text, author: t.authorName})|json_encode|e('html_attr') }}
         }"
         x-init="setInterval(() => { current = (current + 1) % quotes.length }, 8000)">
  
  <div class="container mx-auto">
    <h2 class="text-3xl font-bold text-center mb-12">Depoimentos</h2>
    
    <div class="max-w-4xl mx-auto text-center">
      <template x-for="(quote, index) in quotes" :key="index">
        <div x-show="current === index"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">
          <blockquote class="text-2xl italic mb-6">"<span x-text="quote.text"></span>"</blockquote>
          <p class="font-bold" x-text="quote.author"></p>
        </div>
      </template>
    </div>
  </div>
</section>
```

---

# 5. PAINEL ADMINISTRATIVO

## 5.1 Base Admin Layout (COMPLETO)

```twig
{# templates/admin/base_admin.html.twig #}
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>{% block title %}Admin Procordis{% endblock %}</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    {% block stylesheets %}
        {{ importmap('app') }}
        <style>
            body { font-family: 'Plus Jakarta Sans', sans-serif; }
            .glass-panel {
                background: rgba(255, 255, 255, 0.7);
                backdrop-filter: blur(12px);
                border: 1px solid rgba(255, 255, 255, 0.5);
                box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.05);
            }
            .glass-sidebar {
                background: rgba(255, 255, 255, 0.85);
                backdrop-filter: blur(20px);
                border-right: 1px solid rgba(255, 255, 255, 0.4);
            }
            .mesh-gradient {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                background-image: 
                    radial-gradient(at 40% 20%, hsla(210,100%,93%,1) 0px, transparent 50%),
                    radial-gradient(at 80% 0%, hsla(189,100%,96%,1) 0px, transparent 50%),
                    radial-gradient(at 0% 50%, hsla(341,100%,96%,1) 0px, transparent 50%);
            }
        </style>
    {% endblock %}
    
    {% block javascripts %}
        {{ importmap('app') }}
        <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                tinymce.init({
                    selector: '.editor-html',
                    plugins: 'link image lists',
                    toolbar: 'undo redo | bold italic | link image | numlist bullist',
                    branding: false
                });
            });
        </script>
    {% endblock %}
</head>
<body class="mesh-gradient flex h-screen">
    
    {# Sidebar #}
    <aside class="w-72 flex flex-col glass-sidebar h-full">
        <div class="h-24 flex items-center justify-center border-b">
            <img src="{{ asset('assets/images/logo-9AyYdNa.svg') }}" alt="Procordis" class="h-10">
        </div>
        
        <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1">
            <a href="{{ path('admin_dashboard') }}" 
               class="flex items-center px-4 py-3 rounded-xl {{ app.request.get('_route') == 'admin_dashboard' ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                Dashboard
            </a>
            
            {# More menu items... #}
            <a href="{{ path('admin_doctor_index') }}" class="flex items-center px-4 py-3 rounded-xl text-slate-600 hover:bg-slate-50">
                Equipe Médica
            </a>
            <a href="{{ path('admin_news_index') }}" class="flex items-center px-4 py-3 rounded-xl text-slate-600 hover:bg-slate-50">
                Notícias
            </a>
        </nav>
    </aside>

    {# Main Content #}
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="h-20 glass-panel m-4 rounded-2xl flex items-center justify-between px-8">
            <h1>{% block page_title %}Dashboard{% endblock %}</h1>
            <div class="flex items-center gap-3">
                <span>{{ app.user.email }}</span>
            </div>
        </header>
        
        <main class="flex-1 overflow-auto p-6">
            {% for label, messages in app.flashes %}
                {% for message in messages %}
                    <div class="glass-panel px-6 py-4 rounded-xl mb-6 {{ label == 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800' }}">
                        {{ message }}
                    </div>
                {% endfor %}
            {% endfor %}
            
            {% block body %}{% endblock %}
        </main>
    </div>
</body>
</html>
```

## 5.2 Admin CRUD Template Pattern

```twig
{# templates/admin/doctor/index.html.twig #}
{% extends 'admin/base_admin.html.twig' %}
{% block page_title %}Equipe Médica{% endblock %}

{% block body %}
<div class="glass-panel p-6 rounded-2xl">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Médicos</h2>
        <a href="{{ path('admin_doctor_new') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            + Novo Médico
        </a>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left">Nome</th>
                    <th class="px-4 py-3 text-left">CRM</th>
                    <th class="px-4 py-3 text-left">Especialidade</th>
                    <th class="px-4 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                {% for doctor in doctors %}
                <tr class="border-b hover:bg-slate-50">
                    <td class="px-4 py-3">{{ doctor.name }}</td>
                    <td class="px-4 py-3">{{ doctor.crm }}</td>
                    <td class="px-4 py-3">{{ doctor.specialty }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ path('admin_doctor_edit', {id: doctor.id}) }}" 
                           class="text-blue-600 hover:underline">Editar</a>
                    </td>
                </tr>
                {% endfor %}
            </tbody>
        </table>
    </div>
</div>
{% endblock %}
```

---

# 6. COMANDOS E SEEDING

## 6.1 Populate Test Database (COMPLETO)

```php
<?php
// src/Command/PopulateTestDatabaseCommand.php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;

class PopulateTestDatabaseCommand extends Command
{
    protected static $defaultName = 'app:populate-test-db';

    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $faker = Factory::create('pt_BR');
        
        // Clear existing
        $this->em->createQuery('DELETE FROM App\\Entity\\Doctor')->execute();
        $this->em->createQuery('DELETE FROM App\\Entity\\Service')->execute();
        
        // Create Specialties
        $specialties = [
            ['name' => 'Cardiologia', 'icon' => '<i data-lucide="heart" class="w-12 h-12"></i>'],
            ['name' => 'Neurologia', 'icon' => '<i data-lucide="brain" class="w-12 h-12"></i>'],
        ];
        
        foreach ($specialties as $spec) {
            $specialty = new \App\Entity\Specialty();
            $specialty->setName($spec['name']);
            $specialty->setSvgIcon($spec['icon']);
            $specialty->setActive(true);
            $this->em->persist($specialty);
        }
        
        // Create Doctors
        for ($i = 0; $i < 4; $i++) {
            $doctor = new \App\Entity\Doctor();
            $doctor->setName($faker->name);
            $doctor->setCrm('CRM/SP ' . $faker->numberBetween(10000, 99999));
            $doctor->setSpecialty($faker->randomElement(['Cardiologista', 'Neurologista']));
            $doctor->setBio('<p>' . $faker->paragraph(3) . '</p>');
            $this->em->persist($doctor);
        }
        
        $this->em->flush();
        
        $output->writeln('Database populated successfully!');
        return Command::SUCCESS;
    }
}
```

---

# 7. GUIA DE IMPLEMENTAÇÃO COMPLETO

## 7.1 Setup do Projeto (Do Zero)

```bash
# 1. Clone/Create project
composer create-project symfony/skeleton procordis-site
cd procordis-site

# 2. Install dependencies
composer require webapp
composer require doctrine/orm doctrine/doctrine-bundle
composer require vich/uploader-bundle
composer require symfony/asset-mapper
composer require fakerphp/faker --dev

# 3. Configure database
# Edit .env: DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"

# 4. Setup Tailwind
npm install -D tailwindcss
npx tailwindcss init
# Configure tailwind.config.js (ver seção 1.3)

# 5. Build CSS
npx tailwindcss -i ./assets/app.css -o ./public/css/built.css --watch

# 6. Create entities (ver seção 2.1)
php bin/console make:entity Doctor
# ... (adicionar campos conforme schema)

# 7. Create database & migrations
php bin/console doctrine:database:create
php bin/console make:migration
php bin/console doctrine:migrations:migrate

# 8. Populate data
php bin/console app:populate-test-db

# 9. Create admin user
php bin/console app:admin-user

# 10. Run server
symfony server:start
```

## 7.2 Como Adicionar uma Nova Seção ao Admin

**Exemplo: Adicionar "FAQ"**

```bash
# 1. Criar entidade
php bin/console make:entity Faq
# Adicionar campos: question:text, answer:text, active:boolean, sortOrder:int

# 2. Criar migration
php bin/console make:migration
php bin/console doctrine:migrations:migrate

# 3. Criar Form Type
# src/Form/FaqType.php (copiar pattern de DoctorType)

# 4. Criar Controller
# src/Controller/Admin/FaqController.php (copiar pattern de DoctorController)

# 5. Criar templates
mkdir templates/admin/faq
# Criar: index.html.twig, new.html.twig, edit.html.twig, _form.html.twig

# 6. Adicionar ao menu admin
# Editar templates/admin/base_admin.html.twig
# Adicionar link no sidebar

# 7. Atualizar PopulateTestDatabaseCommand
# Adicionar criação de FAQs de exemplo

# 8. Testar
php bin/console app:populate-test-db
# Acessar /admin/faq
```

## 7.3 Como Adicionar uma Nova Página Pública

```bash
# 1. Criar rota no HomeController
#[Route('/faq', name: 'app_faq')]
public function faq(FaqRepository $faqRepo): Response
{
    return $this->render('home/faq.html.twig', [
        'faqs' => $faqRepo->findBy(['active' => true], ['sortOrder' => 'ASC'])
    ]);
}

# 2. Criar template
# templates/home/faq.html.twig
{% extends 'base_public.html.twig' %}
{% block content %}
  <section class="py-24">
    <div class="container">
      <h1>Perguntas Frequentes</h1>
      {% for faq in faqs %}
        <div>
          <h3>{{ faq.question }}</h3>
          <p>{{ faq.answer }}</p>
        </div>
      {% endfor %}
    </div>
  </section>
{% endblock %}

# 3. Adicionar ao menu
# Editar templates/base_public.html.twig
<a href="{{ path('app_faq') }}">FAQ</a>
```

---

# 8. REFERÊNCIA RÁPIDA

## Rotas Principais

| URL | Controller | Ação |
|-----|------------|------|
| `/` | HomeController::index | Homepage |
| `/sobre` | HomeController::about | Quem Somos |
| `/admin` | DashboardController::index | Admin Dashboard |
| `/admin/doctor` | DoctorController::index | Lista médicos |
| `/admin/doctor/new` | DoctorController::new | Cria médico |
| `/admin/doctor/{id}/edit` | DoctorController::edit | Edita médico |
| `/api/newsletter` | NewsletterApiController::submit | POST subscribe |
| `/api/contact` | ContactApiController::submit | POST message |

## Classes CSS Customizadas

```css
/* Botões */
.btn-medical-primary     /* Botão primário azul */
.btn-medical-outline     /* Botão outline */

/* Inputs */
.form-input             /* Input padrão admin */
.form-textarea          /* Textarea padrão */
.editor-html            /* Ativa TinyMCE */

/* Layout */
.glass-panel            /* Panel com efeito vidro */
.mesh-gradient          /* Background gradient mesh */
```

## Comandos Úteis

```bash
# Limpar cache
php bin/console cache:clear

# Popular dados
php bin/console app:populate-test-db

# Criar admin
php bin/console app:admin-user

# Nova migration
php bin/console make:migration
php bin/console doctrine:migrations:migrate

# Build CSS
npx tailwindcss -i ./assets/app.css -o ./public/css/built.css
```

---

---

# 9. SISTEMA DE TEMAS E INTERFACE ADMINISTRATIVA (2025)

> [!NOTE]
> Esta seção foi adicionada para refletir a arquitetura moderna implementada nas últimas iterações (Dezembro/2025), garantindo alta fidelidade visual (Pixel Perfect) e consistência entre os temas Claro/Escuro.

## 9.1 Arquitetura CSS Escopada (Scoped Themes)

Para evitar conflitos entre o tema público ("Medical Blue") e o painel administrativo ("TailAdmin Dark"), adotamos uma arquitetura de variáveis CSS escopadas. **Não utilizamos** overrides globais com `!important` para tudo.

### Estrutura do `app.css`

1.  **`:root` (Default/Public)**: Define as variáveis padrão para a área pública.
2.  **`.admin-layout` (Escopo Admin)**: Redefine as mesmas variáveis (`--background`, `--card`, `--primary`) especificamente para o Admin.

**Exemplo de Implementação:**

```css
@layer base {
    /* Tema Público */
    :root {
        --background: 0 0% 100%; /* Branco */
        --card: 0 0% 100%;
        --primary: 199 100% 36%; /* Azul Médico */
    }

    /* Tema Admin (High Contrast Dark Mode) */
    .admin-layout.dark {
        --background: 218 45% 11%; /* #0F1828 (Deep Blue) */
        --card: 215 27% 19%;       /* #24303F (Panel Blue) */
        --primary: 233 74% 56%;    /* #3C50E0 (TailAdmin Blue) */
        --border: 212 22% 31%;     /* #3d4d60 */
    }
}
```

Isso permite que templates usem classes utilitárias padrão do Tailwind (ex: `bg-background`, `text-primary`, `border-border`) e automaticamente se adaptem ao contexto (Público ou Admin) sem hacks de especificidade.

## 9.2 Alternância de Tema (Dark Mode Toggle)

Para garantir uma renderização limpa e evitar problemas de cache de CSS (FOUC - Flash of Unstyled Content) ou variáveis "stale" (antigas) persistindo na memória do navegador/Turbo:

1.  **Persistência**: O tema é salvo no `localStorage` ('theme' = 'light' | 'dark').
2.  **Aplicação**: Um script síncrono no `<head>` aplica a classe `dark` ao `html` antes mesmo do conteúdo carregar.
3.  **Botão de Recarregamento**: Ao alterar o tema via botão, **a página é recarregada (`window.location.reload()`)**.
    *   *Por que?* Isso força o browser a repintar (repaint) toda a árvore de estilos, garantindo que as variáveis CSS escopadas sejam recalculadas corretamente para o novo contexto.

```javascript
// assets/js/theme-toggle.js
const toggleTheme = () => {
    // ... lógica de troca ...
    setTheme(newTheme);
    
    // Força recarregamento para limpar cache de estilo e garantir consistência
    setTimeout(() => {
        window.location.reload();
    }, 50);
};
```

## 9.3 Histórico de Evolução e Decisões Técnicas

> Seção criada para guiar futuras reconstruções e entender o "porquê" das decisões atuais.

### Fase 1: Padronização de Headers (Glassmorphism)
Todos os headers (Notícias, Serviços, Transparência) foram padronizados para utilizar o componente visual de "Glass Panel" sobre um fundo gradiente, garantindo consistência visual.
*   **Decisão**: Uso de `vic_uploader_asset` para backgrounds dinâmicos com fallback seguro.

### Fase 2: Correção do Dark Mode (Admin)
O painel administrativo original sofria com baixo contraste e conflitos de cor (azul claro invadindo o dark mode).
*   **Solução**: Implementação do **TailAdmin Theme**.
    *   Fundo: `#0F1828` (Deep Midnight Blue).
    *   Painéis: `#24303F` (Lighter Blue-Grey).
    *   Inputs: `#1D2A39` (Dark Input) com bordas visíveis.
*   **Correção Crítica**: Remoção de seletores "coringa" (wildcard) como `[class*="bg-blue-"]` que causavam falsos positivos no elemento `<body>`, forçando-o a ficar azul.
*   **Cache Busting**: A implementação de `selection:bg-indigo-...` em vez de `blue` foi usada temporariamente para evadir caches persistentes.

### Fase 3: Refatoração para Eficiência (Dez 2025)
Substituímos overrides manuais por uma arquitetura de **CSS Variables Escopadas** (ver seção 9.1). Isso torna o código mais limpo, fácil de manter e previne que mudanças no Admin quebrem o Site Público e vice-versa.

## 9.4 Checklist para Novos Projetos (Baseado nesta Experiência)

1.  **Comece com Escopos**: Nunca defina estilos globais de Dark Mode se o site tiver áreas distintas (Admin vs Público). Crie classes de layout (`.admin-layout`, `.public-layout`) desde o dia 1.
2.  **Variáveis CSS > Utilitários Hardcoded**: Use `bg-background` e defina `--background`, em vez de usar `dark:bg-slate-900`. Isso permite mudar o tema inteiro editando apenas o CSS e não 50 arquivos HTML.
3.  **Reload no Toggle**: Se usar Tailwind Classes complexas e variáveis dinâmicas, o `reload` no toggle de tema economiza horas de debug com estilos "presos".
4.  **Verifique Seletores Globais**: Evite seletores como `div[class*="..."]` em CSS global.

---


# 10. GUIA DE PERFORMANCE E SEO (BLUEPRINT PAGESPEED 100)

> [!IMPORTANT]
> **OBJETIVO CRÍTICO**: Manter PageSpeed Insights 100/100 (Desktop) e +95 (Mobile).
> Qualquer nova feature deve respeitar RIGOROSAMENTE as regras abaixo.

## 10.1 Otimização de Imagens (LiipImagine)

**⚠️ CRÍTICO: Limitações do Servidor**
- **NÃO USE WebP**: O servidor atual não suporta conversão para WebP via GD/Imagick corretamente, gerando erros 500 ou "filter not found".
- **USE JPEG**: Force `jpeg` com qualidade otimizada para todas as fotos.
- **Background**: Ao converter PNG/Transparente para JPEG, defina `background: { color: '#ffffff' }` para evitar fundo preto.
- **Dimensões**: Nunca sirva imagens maiores que o dobro do tamanho de exibição (Retina).

**Configuração Padrão Segura (`config/packages/liip_imagine.yaml`):**
```yaml
filter_sets:
    service_card: # Ex: Card de 300x200px
        quality: 75
        filters:
            thumbnail: { size: [300, 200], mode: outbound }
            background: { color: '#ffffff' } # Essencial para JPEGs
            format: { name: jpeg, quality: 75 } # Força JPEG
```

## 10.2 LCP (Largest Contentful Paint)

O elemento LCP (geralmente o banner principal) deve carregar instantaneamente.

1.  **Preload**: Adicione `rel="preload"` no `<head>` apenas para a imagem LCP da página atual.
    ```twig
    {# templates/news/show.html.twig #}
    {% block stylesheets %}
        <link rel="preload" as="image" href="{{ vich_uploader_asset(news, 'imageFile')|imagine_filter('hero') }}" fetchpriority="high">
    {% endblock %}
    ```
2.  **Fetch Priority**: Na tag `<img>`, use `fetchpriority="high"`.
3.  **CSS Bloqueante**: Mantenha CSS crítico inline se possível, ou preload do `built.css`.

## 10.3 CLS (Cumulative Layout Shift)

**Regra de Ouro**: Tudo deve ter tamanho definido antes de carregar.

1.  **Imagens**: Sempre declare `width` e `height` no HTML, mesmo que o CSS controle o tamanho.
    ```html
    <img src="..." width="400" height="300" class="w-full h-auto">
    ```
2.  **Containers Dinâmicos**: Use `min-height` para áreas que carregam conteúdo depois (ex: `<main>`).
    ```html
    <main class="flex-1 min-h-[50vh]">
    ```
3.  **Fontes**: Use `display=swap` no Google Fonts.

## 10.4 Acessibilidade (Score 100)

1.  **Contraste**: Textos sobre fundo escuro devem ser `text-white` ou muito claros. Evite cinza ou azul escuro sobre preto.
2.  **Área de Toque (Mobile)**: Botões e links devem ter área clicável de pelo menos **48x48px**.
    - *Solução para "bolinhas" de carousel*: Crie um container invisível 48x48px em volta do ponto visual.
    ```html
    <button class="w-12 h-12 flex items-center justify-center ..."> <!-- Hit area -->
        <span class="w-3 h-3 bg-white ..."></span> <!-- Visual dot -->
    </button>
    ```
3.  **Links Descritivos**: Nunca use apenas "Ler mais" ou ícones sem texto.
    - *Solução*: Adicione contexto invisível.
    ```html
    <a href="...">Ler mais <span class="sr-only">sobre Cardiologia</span></a>
    <a href="..."><span class="sr-only">Facebook</span> <i data-lucide="facebook"></i></a>
    ```
4.  **Hierarquia de Títulos**: Respeite H1 -> H2 -> H3. Não pule níveis.

## 10.5 JavaScript e Animações

1.  **Defer**: Todo script externo deve ter `defer`.
2.  **Reflows**: Evite animar propriedades geométricas (`width`, `height`, `margin`) em loops ou scroll.
    - **Preferido**: Anime `transform` e `opacity`.
3.  **Carousel**: Não anime `background-color`. Use transição de `opacity` em elementos sobrepostos.

## 10.6 Pitfalls e "O que NÃO fazer" (Lições Aprendidas)

1.  **HTACCESS Sensível**:
    - **NÃO** remova tags de fechamento `</IfModule>` cegamente.
    - **NÃO** adicione configurações de cache/compressão que o servidor não suporte (causa Error 500).
    - **SEGURO**: `mod_deflate` (Gzip) funciona bem. Headers básicos de segurança funcionam.
2.  **WebP Server-Side**:
    - O servidor atual **NÃO CONVERTE WebP**. Não tente forçar isso no LiipImagine. Otimize JPEGs.
3.  **Lazy Load no LCP**:
    - **JAMAIS** coloque `loading="lazy"` na imagem principal (Banner/Hero). Isso destrói o LCP. Use `eager` ou padrão.

---

**FIM DA DOCUMENTAÇÃO TÉCNICA COMPLETA**

*Este documento deve permitir a reconstrução total do sistema por qualquer desenvolvedor ou IA.*
*Atualizado em: {{ "now"|date("Y-m-d H:i:s") }}*
