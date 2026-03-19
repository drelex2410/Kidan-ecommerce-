<template>
  <div class="tab-layout career-layout">
    <section class="career-section">
      <h2 v-if="tab.intro_title" class="section-heading">{{ tab.intro_title }}</h2>
      <p v-if="tab.intro_body" class="section-copy">{{ tab.intro_body }}</p>
    </section>

    <section v-if="teamMembers.length" class="career-section">
      <h2 class="section-heading">{{ tab.content_title || "Our Team" }}</h2>
      <div class="career-team-grid">
        <article
          v-for="(member, index) in teamMembers"
          :key="`${member.title}-${index}`"
          class="career-team-card"
        >
          <img v-if="member.image" :src="member.image" :alt="member.title || 'Team member'" />
          <h3>{{ member.title }}</h3>
          <p>{{ member.description }}</p>
        </article>
      </div>
    </section>

    <section v-if="opportunities.length" class="career-section">
      <h2 class="section-heading centered">{{ tab.extra_title || "Join Us" }}</h2>
      <div class="career-opportunity-list">
        <article
          v-for="(item, index) in opportunities"
          :key="`${item.title}-${index}`"
          class="career-opportunity-card"
        >
          <img
            v-if="item.image"
            :src="item.image"
            :alt="item.title || 'Opportunity image'"
            class="career-opportunity-avatar"
          />
          <div class="career-opportunity-copy">
            <h3>{{ item.title }}</h3>
            <p>{{ item.description }}</p>
            <a
              v-if="item.button_text && item.button_link"
              class="career-button"
              :href="item.button_link"
            >
              {{ item.button_text }}
            </a>
          </div>
        </article>
      </div>
    </section>
  </div>
</template>

<script>
export default {
  name: "PageTabCareerContent",
  props: {
    tab: {
      type: Object,
      required: true,
    },
  },
  computed: {
    teamMembers() {
      return this.tab.items || [];
    },
    opportunities() {
      return this.tab.items_secondary || [];
    },
  },
};
</script>

<style scoped>
.career-layout {
  color: #171513;
  font-family: Candara, Calibri, "Segoe UI", sans-serif;
  display: grid;
  gap: 2.7rem;
}

.section-heading {
  margin: 0 0 1rem;
  font-size: clamp(1.5875rem, 2.2vw, 1.6875rem);
  font-weight: 700;
  line-height: 1.15;
}

.section-heading.centered {
  text-align: center;
}

.section-copy {
  margin: 0;
  font-size: clamp(1.125rem, 2.4vw, 1.5rem);
  line-height: 1.7;
  white-space: pre-line;
}

.career-team-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1.5rem;
}

.career-team-card img {
  display: block;
  width: 100%;
  aspect-ratio: 0.9;
  object-fit: cover;
  border-radius: 4px;
}

.career-team-card h3 {
  margin: 0.9rem 0 0.35rem;
  font-size: clamp(1.5rem, 2.5vw, 2rem);
  font-weight: 700;
}

.career-team-card p {
  margin: 0;
  color: #4c4a45;
  font-size: clamp(1rem, 1.8vw, 1.2rem);
  line-height: 1.5;
}

.career-opportunity-list {
  display: grid;
  gap: 2rem;
}

.career-opportunity-card {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 1.5rem;
  align-items: start;
}

.career-opportunity-avatar {
  width: 72px;
  height: 72px;
  border-radius: 999px;
  object-fit: cover;
}

.career-opportunity-copy h3 {
  margin: 0 0 0.55rem;
  font-size: clamp(1.45rem, 2.3vw, 1.9rem);
  font-weight: 700;
}

.career-opportunity-copy p {
  margin: 0;
  color: #4c4a45;
  font-size: clamp(1rem, 1.8vw, 1.2rem);
  line-height: 1.7;
  white-space: pre-line;
}

.career-button {
  display: inline-flex;
  margin-top: 1.15rem;
  padding: 0.8rem 1.5rem;
  background: #99180d;
  color: #fff;
  font-size: 1rem;
  font-weight: 700;
  text-decoration: none;
}

@media (max-width: 959px) {
  .career-team-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 699px) {
  .career-team-grid {
    grid-template-columns: 1fr;
  }

  .career-opportunity-card {
    grid-template-columns: 1fr;
  }
}
</style>
