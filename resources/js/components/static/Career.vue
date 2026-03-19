<template>
    <div class="career-page">
        <div class="future-section">
            <h3 class="section-title">Our Future</h3>
            <p class="section-text">
                KIDANSTORE is building a universe: from digital retail to physical pop-up experiences, from curated fashion to
                in-house product lines, from a local platform to a global African luxury movement. This is just the beginning.
            </p>
        </div>

        <div class="team-section">
            <h3 class="section-title-center">Our Team</h3>
            <div class="team-grid">
                <div class="team-card" v-for="member in teamMembers" :key="member.id">
                    <img :src="member.image" class="team-image" :alt="member.name" />
                    <h4 class="team-name">{{ member.name }}</h4>
                    <p class="team-position">{{ member.position }}</p>
                </div>
            </div>
        </div>

        <div class="join-section">
            <h3 class="section-title-center">Join Us</h3>

            <div class="join-item">
                <img src="/public/assets/img/career_circle1.jpg" class="join-icon" alt="Kidan Members" />
                <div class="join-content">
                    <h4 class="join-title">Kidan Members</h4>
                    <p class="join-text">
                        Join KIDAN as we build a lifestyle universe centered around community, sustainability, and impact. We are a lean, efficient, and
                        community-driven team, with members working across the globe to shape a beautiful African future through design, nature,
                        craftsmanship, and products.
                    </p>
                    <button class="join-btn">Join Us</button>
                </div>
            </div>

            <div class="join-item">
                <img src="/public/assets/img/career_circle2.jpg" class="join-icon" alt="Delivery Point" />
                <div class="join-content">
                    <h4 class="join-title">Our Delivery Point</h4>
                    <p class="join-text">
                        Become a KIDAN Delivery Point across cities, states, provinces, and countries. As a delivery partner, you help receive, hold, and
                        distribute KIDAN packages, making delivery faster, smoother, and more connected for our customers. Join our growing
                        ecosystem and support a community-centred network built on convenience and trust.
                    </p>
                    <button class="join-btn">Join Us</button>
                </div>
            </div>

            <div class="join-item">
                <img src="/public/assets/img/career_circle3.jpg" class="join-icon" alt="Designer Partner" />
                <div class="join-content">
                    <h4 class="join-title">Our Delivery Point</h4>
                    <p class="join-text">
                        Join KIDAN as a designer or brand partner and distribute your fashion, beauty, home, or lifestyle products through our growing
                        ecosystem. We support both established brands and emerging creators- helping to showcase, elevate, and scale African
                        design and craftsmanship. Become part of a platform that celebrates culture, quality, and innovation.
                    </p>
                    <button class="join-btn">Join Us</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'CareerPage',
    data() {
        return {
            teamMembers: [
                {
                    id: 1,
                    name: 'Tony Stark',
                    position: 'CEO',
                    image: '/public/assets/img/career1.jpg'
                },
                {
                    id: 2,
                    name: 'Tony Stark',
                    position: 'Product Manager',
                    image: '/public/assets/img/career1.jpg'
                },
                {
                    id: 3,
                    name: 'Tony Stark',
                    position: 'Media Manager',
                    image: '/public/assets/img/career1.jpg'
                }
            ]
        }
    }
}
</script>

<style scoped>
.career-page {
    max-width: 1400px;
    margin: 0 auto;
    padding: 40px 20px;
}

.future-section {
    margin-bottom: 60px;
}

.section-title {
    font-size: 32px;
    font-weight: 700;
    color: #000;
    margin-bottom: 20px;
    font-family: Arial, sans-serif;
}

.section-title-center {
    font-size: 32px;
    font-weight: 700;
    color: #000;
    margin-bottom: 40px;
    font-family: Arial, sans-serif;
    text-align: center;
}

.section-text {
    font-size: 16px;
    line-height: 1.8;
    color: #333;
    max-width: 1200px;
    font-family: Arial, sans-serif;
}

.team-section {
    margin-bottom: 80px;
}

.team-grid {
    display: flex;
    justify-content: center;
    gap: 40px;
    flex-wrap: wrap;
}

.team-card {
    display: flex;
    flex-direction: column;
    text-align: center;
}

.team-image {
    width: 345px;
    height: 360px;
    object-fit: cover;
    display: block;
    margin-bottom: 16px;
}

.team-name {
    font-size: 20px;
    font-weight: 700;
    color: #000;
    margin: 0 0 4px 0;
    font-family: Arial, sans-serif;
}

.team-position {
    font-size: 14px;
    color: #999;
    margin: 0;
    font-family: Arial, sans-serif;
}

.join-section {
    margin-top: 80px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.join-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 60px;
    gap: 30px;
    max-width: 1100px;
    width: 100%;
}

.join-icon {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.join-content {
    flex: 1;
}

.join-title {
    font-size: 22px;
    font-weight: 700;
    color: #000;
    margin: 0 0 16px 0;
    font-family: Arial, sans-serif;
}

.join-text {
    font-size: 15px;
    line-height: 1.7;
    color: #666;
    margin: 0 0 20px 0;
    font-family: Arial, sans-serif;
}

.join-btn {
    background: #8B0000;
    color: white;
    border: none;
    padding: 12px 36px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s ease;
    font-family: Arial, sans-serif;
}

.join-btn:hover {
    background: #a00000;
}

@media (max-width: 1200px) {
    .team-image {
        width: 100%;
        max-width: 345px;
        height: auto;
        aspect-ratio: 345/360;
    }
}

@media (max-width: 768px) {
    .team-grid {
        flex-direction: column;
        align-items: center;
        gap: 30px;
    }

    .join-item {
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 20px;
    }

    .join-icon {
        width: 80px;
        height: 80px;
    }
}

@media (max-width: 599px) {
    .career-page {
        padding: 20px 16px;
    }

    .section-title,
    .section-title-center {
        font-size: 26px;
    }

    .section-text {
        font-size: 14px;
    }

    .join-title {
        font-size: 18px;
    }

    .join-text {
        font-size: 14px;
    }
}
</style>