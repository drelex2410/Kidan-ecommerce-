<template>
  <div class="tribe-page">
    <!-- Header Section - LEFT ALIGNED -->
    <div class="tribe-header mb-10">
      <h2 class="text-h3 font-weight-bold mb-6">You should know</h2>
      <p class="text-body-1 intro-text">
        As a customer, you automatically become part of the KIDAN Tribe - our community of loyal members who 
        enjoy a world of rewards, exclusive access, and lifestyle privileges. Your membership unlocks our tiered 
        reward system, with each level offering unique benefits that deepen your connection to KIDAN. From early 
        product access to curated lifestyle perks, every tier brings you closer to the heart of KIDAN.
      </p>
    </div>

    <!-- Horizontal Section: KIDAN TRIBE (Left) and REWARD (Right) -->
    <div class="horizontal-section">
      <!-- Left: KIDAN TRIBE TIERS Title -->
      <div class="tribe-title-section">
        <h1 class="tribe-title">
          KIDAN <span class="tribe-title-light">TRIBE</span>
        </h1>
        <h2 class="tiers-subtitle">TIERS</h2>
      </div>

      <!-- Right: REWARD Section -->
      <div class="reward-section">
        <div class="reward-header">
          <svg width="70" height="70" viewBox="0 0 70 70" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- Green badge with ribbons -->
            <circle cx="35" cy="35" r="25" fill="#4CAF50"/>
            <circle cx="35" cy="35" r="20" fill="#66BB6A"/>
            <circle cx="35" cy="35" r="15" fill="#81C784"/>
            
            <!-- Ribbon tails -->
            <path d="M25 50 L20 65 L25 60 L30 65 Z" fill="#388E3C"/>
            <path d="M45 50 L50 65 L45 60 L40 65 Z" fill="#388E3C"/>
            
            <!-- Star in center -->
            <path d="M35 28 L37 33 L42 33 L38 36 L40 41 L35 38 L30 41 L32 36 L28 33 L33 33 Z" fill="#FFF"/>
          </svg>
          <h3 class="reward-title">REWARD</h3>
        </div>
      </div>
    </div>

    <!-- Tiers Table - FULL WIDTH BELOW -->
    <div class="tiers-table-section">
      <div class="tiers-table">
        <div class="tier-row">
          <div class="tier-name">KIDAN TRIBE MEMBER</div>
          <div class="tier-card">
            <svg width="35" height="35" viewBox="0 0 35 35" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="17.5" cy="17.5" r="16" fill="#B8C5D6" stroke="#8B9DB8" stroke-width="1.5"/>
              <circle cx="17.5" cy="17.5" r="12" fill="#D4DFEC" opacity="0.6"/>
              <path d="M17.5 8 L21 14 L17.5 12.5 L14 14 Z" fill="#E8EEF7"/>
            </svg>
            <span class="card-label">Silver Card</span>
          </div>
        </div>

        <div class="tier-row">
          <div class="tier-name">KIDAN TRIBE INSIDER</div>
          <div class="tier-card">
            <svg width="35" height="35" viewBox="0 0 35 35" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="17.5" cy="17.5" r="16" fill="#D4AF37" stroke="#B8941F" stroke-width="1.5"/>
              <circle cx="17.5" cy="17.5" r="12" fill="#F4D03F" opacity="0.5"/>
              <path d="M17.5 8 L21 14 L17.5 12.5 L14 14 Z" fill="#FFF8DC"/>
            </svg>
            <span class="card-label">Gold Card</span>
          </div>
        </div>

        <div class="tier-row">
          <div class="tier-name">KIDAN TRIBE MENTOR</div>
          <div class="tier-card">
            <svg width="35" height="35" viewBox="0 0 35 35" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="17.5" cy="17.5" r="16" fill="#2C2C2C" stroke="#1a1a1a" stroke-width="1.5"/>
              <circle cx="17.5" cy="17.5" r="12" fill="#404040" opacity="0.6"/>
              <path d="M17.5 8 L21 14 L17.5 12.5 L14 14 Z" fill="#555555"/>
            </svg>
            <span class="card-label">Black Card</span>
          </div>
        </div>

        <div class="tier-row">
          <div class="tier-name">KIDAN TRIBE ROYAL</div>
          <div class="tier-card">
            <svg width="35" height="35" viewBox="0 0 35 35" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="17.5" cy="17.5" r="16" fill="#6B2C3E" stroke="#4a1f2b" stroke-width="1.5"/>
              <circle cx="17.5" cy="17.5" r="12" fill="#8B3A52" opacity="0.5"/>
              <path d="M17.5 8 L21 14 L17.5 12.5 L14 14 Z" fill="#a04862"/>
            </svg>
            <span class="card-label">Maroon VIP Card</span>
          </div>
        </div>
      </div>

      <p class="tier-description mt-6">
        Each tier reflects your journey with us and offers elevated experiences designed to celebrate your style, 
        loyalty, and presence within the KIDAN lifestyle ecosystem. Shop. Earn. Grow with us.
      </p>
    </div>

    <!-- Reward Illustration - BELOW THE TABLE -->
    <div class="reward-illustration-bottom">
      <svg width="450" height="280" viewBox="0 0 450 280" fill="none" xmlns="http://www.w3.org/2000/svg">
        <!-- Large gold star -->
        <path d="M225 50 L245 100 L298 107 L262 142 L271 195 L225 170 L179 195 L188 142 L152 107 L205 100 Z" 
              fill="#FFD700" stroke="#FFA000" stroke-width="3"/>
        
        <!-- Medium star right -->
        <path d="M320 110 L333 140 L365 145 L343 166 L348 199 L320 183 L292 199 L297 166 L275 145 L307 140 Z" 
              fill="#FFC107" stroke="#FF8F00" stroke-width="2"/>
        
        <!-- Medium star left -->
        <path d="M130 110 L143 140 L175 145 L153 166 L158 199 L130 183 L102 199 L107 166 L85 145 L117 140 Z" 
              fill="#FFC107" stroke="#FF8F00" stroke-width="2"/>
        
        <!-- Gift box -->
        <rect x="175" y="200" width="100" height="70" rx="4" fill="#6D4C41"/>
        <rect x="175" y="200" width="100" height="18" fill="#8D6E63"/>
        
        <!-- Ribbon on box -->
        <rect x="220" y="200" width="10" height="70" fill="#D7CCC8"/>
        <path d="M220 200 L225 185 L230 200" stroke="#BCAAA4" stroke-width="3" fill="none" stroke-linecap="round"/>
        
        <!-- Sparkles -->
        <circle cx="110" cy="70" r="4" fill="#FFD700"/>
        <circle cx="340" cy="70" r="4" fill="#FFD700"/>
        <circle cx="90" cy="180" r="3" fill="#FFC107"/>
        <circle cx="360" cy="180" r="3" fill="#FFC107"/>
        <circle cx="160" cy="40" r="2" fill="#FFE082"/>
        <circle cx="290" cy="40" r="2" fill="#FFE082"/>
        
        <!-- Star sparkles -->
        <path d="M170 60 L172 66 L178 68 L172 70 L170 76 L168 70 L162 68 L168 66 Z" fill="#FFD700"/>
        <path d="M280 60 L282 66 L288 68 L282 70 L280 76 L278 70 L272 68 L278 66 Z" fill="#FFD700"/>
        <path d="M100 150 L101 154 L105 155 L101 156 L100 160 L99 156 L95 155 L99 154 Z" fill="#FFCA28"/>
        <path d="M350 150 L351 154 L355 155 L351 156 L350 160 L349 156 L345 155 L349 154 Z" fill="#FFCA28"/>
        
        <!-- Additional sparkles around stars -->
        <circle cx="250" cy="80" r="2" fill="#FFE082"/>
        <circle cx="200" cy="80" r="2" fill="#FFE082"/>
        <path d="M140 200 L141 203 L144 204 L141 205 L140 208 L139 205 L136 204 L139 203 Z" fill="#FFCA28"/>
        <path d="M310 200 L311 203 L314 204 L311 205 L310 208 L309 205 L306 204 L309 203 Z" fill="#FFCA28"/>
      </svg>
    </div>
  </div>
</template>

<style scoped>
.tribe-page {
  padding: 60px 80px;
  background: #FFFBF3;
  max-width: 1200px;
  margin: 0 auto;
}

/* Header - LEFT ALIGNED */
.tribe-header {
  text-align: left;
  margin-bottom: 50px;
}

.tribe-header h2 {
  text-align: left;
  font-size: 2.5rem;
  color: #000;
}

.intro-text {
  text-align: left;
  max-width: 100%;
  color: #555;
  line-height: 1.8;
  font-size: 1.05rem;
}

/* Horizontal Section: KIDAN TRIBE (Left) and REWARD (Right) */
.horizontal-section {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 40px;
  flex-wrap: wrap;
  gap: 20px;
}

/* Left: KIDAN TRIBE TIERS Title */
.tribe-title-section {
  text-align: left;
  flex: 1;
  min-width: 300px;
}

.tribe-title {
  font-size: 3.5rem;
  font-weight: 700;
  color: #000;
  letter-spacing: 2px;
  line-height: 1;
  margin-bottom: 0;
}

.tribe-title-light {
  font-weight: 300;
}

.tiers-subtitle {
  font-size: 2.5rem;
  font-weight: 700;
  color: #8B0000;
  letter-spacing: 2px;
  margin-top: 5px;
}

/* Right: REWARD Section */
.reward-section {
  flex: 1;
  min-width: 300px;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  justify-content: flex-start;
  text-align: right;
}

.reward-header {
  display: flex;
  align-items: center;
  gap: 18px;
  justify-content: flex-end;
}

.reward-title {
  font-size: 2.8rem;
  font-weight: 700;
  color: #6BBE68;
  letter-spacing: 2px;
  margin: 0;
}

/* Tiers Table - FULL WIDTH */
.tiers-table-section {
  width: 100%;
  margin-bottom: 40px;
}

.tiers-table {
  border: 2px solid #64B5F6;
  border-radius: 0;
  overflow: hidden;
  width: 100%;
}

.tier-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 22px 28px;
  border-bottom: 1px solid #E0E0E0;
  background: #FEFEFE;
  width: 100%;
}

.tier-row:last-child {
  border-bottom: none;
}

.tier-name {
  font-size: 1.05rem;
  font-weight: 700;
  color: #000;
  letter-spacing: 0.5px;
  flex: 1;
}

.tier-card {
  display: flex;
  align-items: center;
  gap: 12px;
}

.card-label {
  font-size: 0.95rem;
  color: #757575;
  font-weight: 400;
}

.tier-description {
  color: #555;
  line-height: 1.8;
  font-size: 1.05rem;
  text-align: left;
  width: 100%;
}

/* Reward Illustration - Below Table */
.reward-illustration-bottom {
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: 40px;
}

.reward-illustration-bottom svg {
  width: 100%;
  max-width: 450px;
  height: auto;
}

/* Responsive Design */
@media (max-width: 1199px) {
  .tribe-page {
    padding: 50px 60px;
  }
  
  .tribe-title {
    font-size: 3rem;
  }
  
  .tiers-subtitle {
    font-size: 2.2rem;
  }
  
  .reward-title {
    font-size: 2.5rem;
  }
}

@media (max-width: 959px) {
  .tribe-page {
    padding: 40px 30px;
  }
  
  .horizontal-section {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }
  
  .tribe-title-section {
    text-align: center;
    width: 100%;
  }
  
  .reward-section {
    align-items: center;
    text-align: center;
    width: 100%;
  }
  
  .reward-header {
    justify-content: center;
  }
  
  .tribe-title {
    font-size: 2.8rem;
  }
  
  .tiers-subtitle {
    font-size: 2rem;
  }
  
  .reward-title {
    font-size: 2.5rem;
  }
  
  .reward-illustration-bottom svg {
    max-width: 400px;
  }
}

@media (max-width: 767px) {
  .tribe-page {
    padding: 30px 20px;
  }
  
  .tribe-header h2 {
    font-size: 2rem;
  }
  
  .tribe-title {
    font-size: 2.5rem;
  }
  
  .tiers-subtitle {
    font-size: 1.8rem;
  }
  
  .reward-title {
    font-size: 2.2rem;
  }
  
  .reward-header svg {
    width: 60px;
    height: 60px;
  }
  
  .tier-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
    padding: 18px 20px;
  }
  
  .tier-card {
    width: 100%;
    justify-content: flex-start;
  }
  
  .reward-illustration-bottom svg {
    max-width: 350px;
  }
}

@media (max-width: 599px) {
  .tribe-page {
    padding: 25px 16px;
  }
  
  .tribe-header h2 {
    font-size: 1.8rem;
  }
  
  .intro-text {
    font-size: 1rem;
  }
  
  .tribe-title {
    font-size: 2.2rem;
  }
  
  .tiers-subtitle {
    font-size: 1.6rem;
  }
  
  .reward-title {
    font-size: 2rem;
  }
  
  .reward-header svg {
    width: 50px;
    height: 50px;
  }
  
  .reward-illustration-bottom svg {
    max-width: 300px;
    height: 200px;
  }
  
  .tier-name {
    font-size: 1rem;
  }
  
  .card-label {
    font-size: 0.9rem;
  }
  
  .tier-description {
    font-size: 1rem;
  }
}

@media (max-width: 399px) {
  .tribe-title {
    font-size: 1.8rem;
  }
  
  .tiers-subtitle {
    font-size: 1.4rem;
  }
  
  .reward-title {
    font-size: 1.8rem;
  }
  
  .tier-row {
    padding: 16px;
  }
  
  .reward-illustration-bottom svg {
    max-width: 280px;
    height: 180px;
  }
}
</style>