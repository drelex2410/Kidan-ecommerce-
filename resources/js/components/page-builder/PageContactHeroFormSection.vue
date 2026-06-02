<template>
  <section class="contact-section">
    <div
      class="contact-hero"
      :style="{ backgroundImage: `linear-gradient(rgba(0,0,0,.2), rgba(0,0,0,.18)), url('${heroImage}')` }"
    >
      <div class="contact-hero-copy">
        <h1>{{ title }}</h1>
        <p>{{ subtitle }}</p>
      </div>
    </div>

    <div class="contact-card-shell">
      <div class="contact-card">
        <aside class="contact-info-panel">
          <h2>Contact Information</h2>
          <p>{{ data.contact_intro || "Reach out to us." }}</p>

          <div class="contact-info-row">
            <span class="contact-icon">i</span>
            <div>
              <h3>{{ data.location_label || "Location" }}</h3>
              <strong>{{ data.location_text }}</strong>
            </div>
          </div>

          <div class="contact-info-row">
            <span class="contact-icon">@</span>
            <div>
              <h3>{{ data.mail_label || "Mail" }}</h3>
              <strong>{{ data.mail_text }}</strong>
            </div>
          </div>

          <div class="contact-info-row">
            <span class="contact-icon">P</span>
            <div>
              <h3>{{ data.phone_label || "Phone" }}</h3>
              <strong>{{ data.phone_text }}</strong>
            </div>
          </div>

          <span class="panel-shape" aria-hidden="true"></span>
        </aside>

        <form class="contact-form-panel" @submit.prevent="submitForm" novalidate>
          <div v-if="successMessage" class="form-alert form-alert--success">{{ successMessage }}</div>
          <div v-if="errorMessage" class="form-alert form-alert--error">{{ errorMessage }}</div>

          <label>
            <span>Full Name</span>
            <input v-model.trim="form.full_name" type="text" autocomplete="name" />
            <small v-if="errors.full_name">{{ errors.full_name }}</small>
          </label>

          <label>
            <span>Email Address</span>
            <input v-model.trim="form.email" type="email" autocomplete="email" />
            <small v-if="errors.email">{{ errors.email }}</small>
          </label>

          <label>
            <span>Send Message</span>
            <textarea v-model.trim="form.message" rows="5"></textarea>
            <small v-if="errors.message">{{ errors.message }}</small>
          </label>

          <p v-if="form.inquiry_type" class="selected-inquiry">
            Inquiry: {{ form.inquiry_type }}
          </p>

          <button type="submit" :disabled="submitting">
            {{ submitting ? "Sending..." : "Send Message" }}
          </button>
        </form>
      </div>
    </div>
  </section>
</template>

<script>
export default {
  name: "PageContactHeroFormSection",
  props: {
    section: { type: Object, required: true },
  },
  data() {
    return {
      form: {
        full_name: "",
        email: "",
        message: "",
        inquiry_type: "",
        source_page: "contact-us",
      },
      errors: {},
      errorMessage: "",
      successMessage: "",
      submitting: false,
    };
  },
  computed: {
    data() {
      return this.section.data || {};
    },
    title() {
      return this.data.heading || this.data.title || "Contact Us";
    },
    subtitle() {
      return this.data.subheading || this.data.subtitle || "";
    },
    heroImage() {
      return this.data.image || this.data.fallback_image_url || "/assets/img/about_hero.jpg";
    },
  },
  mounted() {
    window.addEventListener("kidan-contact-inquiry", this.handleInquirySelection);
  },
  beforeUnmount() {
    window.removeEventListener("kidan-contact-inquiry", this.handleInquirySelection);
  },
  methods: {
    handleInquirySelection(event) {
      this.form.inquiry_type = event.detail?.inquiry_type || "";
    },
    validateForm() {
      const errors = {};
      if (!this.form.full_name) {
        errors.full_name = "Full name is required.";
      }
      if (!this.form.email) {
        errors.email = "Email address is required.";
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) {
        errors.email = "Please enter a valid email address.";
      }
      if (!this.form.message) {
        errors.message = "Message is required.";
      }

      this.errors = errors;
      return Object.keys(errors).length === 0;
    },
    async submitForm() {
      this.errorMessage = "";
      this.successMessage = "";
      if (!this.validateForm()) {
        return;
      }

      this.submitting = true;
      try {
        const res = await this.call_api("post", "contact-submissions", this.form);
        if (res?.data?.success) {
          this.successMessage = res.data.message || "Your message has been sent.";
          this.form.full_name = "";
          this.form.email = "";
          this.form.message = "";
          this.form.inquiry_type = "";
          this.errors = {};
        } else {
          this.errorMessage = res?.data?.message || "We could not send your message yet.";
        }
      } catch (error) {
        const validationErrors = error?.response?.data?.errors || {};
        this.errors = Object.fromEntries(
          Object.entries(validationErrors).map(([key, value]) => [key, Array.isArray(value) ? value[0] : value])
        );
        this.errorMessage = error?.response?.data?.message || "Please check the form and try again.";
      } finally {
        this.submitting = false;
      }
    },
  },
};
</script>

<style scoped>
.contact-section {
  --cream: #fbf7ef;
  --maroon: #990000;
  background: var(--cream);
}
.contact-hero {
  min-height: 420px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-position: center 38%;
  background-size: cover;
}
.contact-hero-copy {
  width: min(780px, calc(100% - 32px));
  text-align: center;
  color: #fff;
  transform: translateY(-8px);
}
.contact-hero-copy h1 {
  margin: 0 0 12px;
  font-size: 38px;
  font-weight: 800;
  line-height: 1.1;
}
.contact-hero-copy p {
  margin: 0 auto;
  max-width: 740px;
  font-size: 18px;
  line-height: 1.65;
}
.contact-card-shell {
  width: min(1080px, calc(100% - 32px));
  margin: -118px auto 0;
  position: relative;
  z-index: 2;
}
.contact-card {
  display: grid;
  grid-template-columns: minmax(300px, 430px) 1fr;
  overflow: hidden;
  border-radius: 4px;
  background: #fff;
}
.contact-info-panel {
  position: relative;
  min-height: 430px;
  padding: 42px 40px;
  overflow: hidden;
  background: var(--maroon);
  color: #fff;
}
.contact-info-panel h2 {
  margin: 0 0 8px;
  font-size: 20px;
}
.contact-info-panel > p {
  margin: 0 0 28px;
  font-size: 16px;
  opacity: .9;
}
.contact-info-row {
  display: grid;
  grid-template-columns: 34px 1fr;
  gap: 14px;
  margin: 0 0 28px;
  position: relative;
  z-index: 1;
}
.contact-icon {
  display: inline-grid;
  width: 24px;
  height: 24px;
  place-items: center;
  font-weight: 800;
}
.contact-info-row h3 {
  margin: 0 0 10px;
  font-size: 18px;
  font-weight: 400;
}
.contact-info-row strong {
  display: block;
  max-width: 310px;
  font-size: 15px;
  line-height: 1.45;
}
.panel-shape {
  position: absolute;
  right: -70px;
  bottom: -75px;
  width: 230px;
  height: 230px;
  background: #e9d5a9;
  border-radius: 52% 48% 0 52%;
  transform: rotate(28deg);
}
.contact-form-panel {
  padding: 48px 72px 42px;
}
.contact-form-panel label {
  display: block;
  margin-bottom: 26px;
}
.contact-form-panel span {
  display: block;
  margin-bottom: 10px;
  color: #504a43;
  font-size: 14px;
}
.contact-form-panel input,
.contact-form-panel textarea {
  width: 100%;
  border: 0;
  border-bottom: 1px solid #cfc8bd;
  border-radius: 0;
  outline: 0;
  resize: vertical;
  background: transparent;
  color: #17110d;
  font-size: 16px;
}
.contact-form-panel input {
  height: 36px;
}
.contact-form-panel small {
  display: block;
  margin-top: 6px;
  color: #990000;
}
.selected-inquiry {
  margin: -8px 0 18px;
  color: #71675e;
  font-size: 14px;
}
.contact-form-panel button {
  width: 100%;
  border: 0;
  border-radius: 4px;
  padding: 18px 24px;
  background: var(--maroon);
  color: #fff;
  font-weight: 800;
}
.contact-form-panel button:disabled {
  opacity: .7;
}
.form-alert {
  margin-bottom: 18px;
  padding: 12px 14px;
  border-radius: 4px;
  font-size: 14px;
}
.form-alert--success {
  background: #eef8ef;
  color: #1e642b;
}
.form-alert--error {
  background: #fff1f0;
  color: #990000;
}
@media (max-width: 900px) {
  .contact-hero { min-height: 360px; }
  .contact-card { grid-template-columns: 1fr; }
  .contact-info-panel { min-height: auto; }
  .contact-form-panel { padding: 34px 28px; }
}
@media (max-width: 560px) {
  .contact-hero-copy h1 { font-size: 32px; }
  .contact-hero-copy p { font-size: 15px; }
  .contact-card-shell { margin-top: -72px; }
  .contact-info-panel { padding: 32px 24px; }
}
</style>
