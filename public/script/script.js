function initHeroAnimations() {
  const slides = document.querySelectorAll('#hero-carousel .carousel-slide');
  const title = document.getElementById('hero-title');
  const subtitle = document.getElementById('hero-subtitle');
  const button = document.getElementById('hero-button');

  const slideContent = [
    { title: "Welcome to Hotel Diongco", subtitle: "Where comfort meets elegance.", buttonText: "Book Now", href: "/booking" },
    { title: "Relax in Style", subtitle: "Modern rooms designed for your comfort", buttonText: "", href: "" },
    { title: "Dine & Indulge", subtitle: "Savor exquisite cuisine and drinks", buttonText: "", href: "" },
    { title: "Wellness & Leisure", subtitle: "Rejuvenate in our spa and wellness facilities", buttonText: "", href: "" }
  ];

  let current = 0;

  function showSlide(index) {
    slides.forEach((slide, i) => slide.style.opacity = i === index ? '1' : '0');

    title?.classList.add('opacity-0');
    subtitle?.classList.add('opacity-0');
    button?.classList.add('opacity-0');

    setTimeout(() => {
      if(title) title.textContent = slideContent[index].title;
      if(subtitle) subtitle.textContent = slideContent[index].subtitle;

      if(button) {
        if(slideContent[index].buttonText) {
          button.textContent = slideContent[index].buttonText;
          button.href = slideContent[index].href;
          button.classList.remove('hidden');
        } else {
          button.classList.add('hidden');
        }
      }

      title?.classList.remove('opacity-0');
      subtitle?.classList.remove('opacity-0');
      button?.classList.remove('opacity-0');
    }, 300);
  }

  function nextSlide() {
    current = (current + 1) % slides.length;
    showSlide(current);
  }

  if(slides.length > 0) {
    showSlide(current);
    setInterval(nextSlide, 7000);
  }


  const aboutHeroTitle = document.getElementById('about-hero-title');
  if(aboutHeroTitle) {
    setTimeout(() => {
      aboutHeroTitle.classList.remove('opacity-0', 'translate-y-6');
      aboutHeroTitle.classList.add('opacity-100', 'translate-y-0');
    }, 200);
  }
}


document.addEventListener('DOMContentLoaded', initHeroAnimations);


document.addEventListener('turbo:load', initHeroAnimations);
