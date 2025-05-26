document.addEventListener("DOMContentLoaded", function () {
  const forms = document.querySelectorAll(".multi-step-form");

  forms.forEach((form) => {
    const steps = form.querySelectorAll(".form-step");
    const nextButtons = form.querySelectorAll(".next-step");
    const prevButtons = form.querySelectorAll(".prev-step");
    const progressBar = form.querySelector(".progress-bar");

    // Initialiser la première étape
    steps[0].classList.add("active");
    updateProgressBar(0);

    // Gérer les boutons "Suivant"
    nextButtons.forEach((button) => {
      button.addEventListener("click", function (e) {
        e.preventDefault();
        const currentStep = this.closest(".form-step");
        const nextStep = currentStep.nextElementSibling;

        // Valider l'étape courante
        if (validateStep(currentStep)) {
          currentStep.classList.remove("active");
          nextStep.classList.add("active");
          updateProgressBar(getCurrentStepIndex(currentStep) + 1);
        }
      });
    });

    // Gérer les boutons "Précédent"
    prevButtons.forEach((button) => {
      button.addEventListener("click", function (e) {
        e.preventDefault();
        const currentStep = this.closest(".form-step");
        const prevStep = currentStep.previousElementSibling;

        currentStep.classList.remove("active");
        prevStep.classList.add("active");
        updateProgressBar(getCurrentStepIndex(currentStep) - 1);
      });
    });

    // Fonction pour mettre à jour la barre de progression
    function updateProgressBar(currentStepIndex) {
      const totalSteps = steps.length;
      const progress = ((currentStepIndex + 1) / totalSteps) * 100;
      progressBar.style.width = progress + "%";
    }

    // Fonction pour obtenir l'index de l'étape courante
    function getCurrentStepIndex(step) {
      return Array.from(steps).indexOf(step);
    }

    // Fonction pour valider une étape
    function validateStep(step) {
      const inputs = step.querySelectorAll(
        "input[required], select[required], textarea[required]"
      );
      let isValid = true;

      inputs.forEach((input) => {
        if (!input.value.trim()) {
          isValid = false;
          input.classList.add("is-invalid");
        } else {
          input.classList.remove("is-invalid");
        }
      });

      return isValid;
    }
  });
});
