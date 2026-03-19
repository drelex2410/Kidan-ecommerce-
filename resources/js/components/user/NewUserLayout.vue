<template>
  <div class="user-layout">
    <div class="layout-grid">
      <div class="left-column">
        <div class="user-info-box">
          <div class="avatar-circle">{{ initials }}</div>
          <div class="user-details">
            <div class="user-name">{{ currentUser.name }}</div>
            <div class="user-email">{{ currentUser.email }}</div>
          </div>
        </div>

        <div class="stats-container">
          <div class="stat-item">
            <div class="stat-icon box-icon">
              <svg viewBox="0 0 24 24" fill="none">
                <path
                  d="M20 7H4C2.89543 7 2 7.89543 2 9V19C2 20.1046 2.89543 21 4 21H20C21.1046 21 22 20.1046 22 19V9C22 7.89543 21.1046 7 20 7Z"
                  stroke="#d4a574" stroke-width="2" />
                <path
                  d="M16 21V5C16 4.46957 15.7893 3.96086 15.4142 3.58579C15.0391 3.21071 14.5304 3 14 3H10C9.46957 3 8.96086 3.21071 8.58579 3.58579C8.21071 3.96086 8 4.46957 8 5V21"
                  stroke="#d4a574" stroke-width="2" />
              </svg>
            </div>
            <div class="stat-info">
              <div class="stat-label">Total Orders</div>
              <div class="stat-value">1</div>
            </div>
          </div>

          <div class="stat-item">
            <div class="stat-icon sun-icon">
              <svg viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="5" stroke="#f4b942" stroke-width="2" />
                <path
                  d="M12 1V3M12 21V23M23 12H21M3 12H1M20.66 3.34L19.07 4.93M4.93 19.07L3.34 20.66M20.66 20.66L19.07 19.07M4.93 4.93L3.34 3.34"
                  stroke="#f4b942" stroke-width="2" />
              </svg>
            </div>
            <div class="stat-info">
              <div class="stat-label">Total Points</div>
              <div class="stat-value">4</div>
            </div>
          </div>
        </div>

        <div class="membership-box">
          <div class="membership-top">
            <span class="membership-label">KIDAN TRIBE MEMBER</span>
            <svg class="info-svg" width="14" height="14" viewBox="0 0 24 24" fill="none">
              <circle cx="12" cy="12" r="10" stroke="#7cb3d9" stroke-width="2" />
              <path d="M12 16V12M12 8H12.01" stroke="#7cb3d9" stroke-width="2" stroke-linecap="round" />
            </svg>
          </div>
          <div class="progress-container">
            <div class="progress-bar"></div>
          </div>
          <div class="points-info"><strong>104</strong> Points till the next level</div>
        </div>

        <div class="earn-box">
          <div class="earn-heading">How to earn points</div>
          <ul class="points-list">
            <li>Lorem ipsum dolor sit amet, consectetuer adipiscing elit.</li>
            <li>Lorem ipsum dolor sit amet, consectetuer adipiscing elit.</li>
            <li>Lorem ipsum dolor sit amet, consectetuer adipiscing elit.</li>
          </ul>
        </div>
      </div>

      <div class="right-column">
        <div class="nav-bar">
          <div class="nav-links">
            <router-link v-for="(item, i) in routes" :key="i" :to="{ name: item.to }" class="nav-item"
              active-class="active">
              {{ item.label }}
            </router-link>
          </div>
          <a class="logout" @click="logout">LogOut</a>
        </div>

        <div class="content-section">
          <router-view></router-view>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters, mapActions } from "vuex";
export default {
  computed: {
    ...mapGetters("auth", ["currentUser"]),
    routes() {
      return [
        { label: "Dashboard", to: "DashBoard" },
        { label: "My Orders", to: "Orders" },
        { label: "Wishlist", to: "Wishlist" },
        // { label: "Reviews", to: "Reviews" },
        { label: "Wallet", to: "Wallet" },
        { label: "Manage Profile", to: "Profile" }
      ];
    },
    initials() {
      if (!this.currentUser.name) return "";
      return this.currentUser.name
        .split(" ")
        .map((n) => n[0])
        .join("")
        .toUpperCase()
        .slice(0, 2);
    }
  },
  methods: {
    ...mapActions(["auth/logout"]),
    async logout() {
      await this.call_api("get", "auth/logout");
      this["auth/logout"]();
      this.$router.push({ name: "Home" }).catch(() => { });
    }
  }
};
</script>

<style scoped lang="scss">
.user-layout {
  background: #FFFBF3;
  padding: 40px 50px;
  width: 100%;
  max-width: 100vw;
  overflow-x: hidden;
  box-sizing: border-box;

  @media (max-width: 768px) {
    padding: 20px 15px;
  }

  @media (max-width: 480px) {
    padding: 15px 10px;
  }
}

.layout-grid {
  display: grid;
  grid-template-columns: 290px 1fr;
  gap: 50px;
  max-width: 1400px;
  margin: 0 auto;
  width: 100%;
  box-sizing: border-box;

  @media (max-width: 1024px) {
    grid-template-columns: 250px 1fr;
    gap: 30px;
  }

  @media (max-width: 768px) {
    grid-template-columns: 1fr;
    gap: 20px;
  }
}

.left-column {
  background: #FFFBF3;
  border: 1px solid #ddd;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;

  @media (max-width: 768px) {
    order: 2;
  }
}

.user-info-box {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 22px 20px;
  border-bottom: 1px solid #e8e8e8;

  @media (max-width: 480px) {
    padding: 18px 15px;
  }
}

.avatar-circle {
  width: 52px;
  height: 52px;
  background: #000;
  color: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  font-weight: 600;
  flex-shrink: 0;

  @media (max-width: 480px) {
    width: 48px;
    height: 48px;
    font-size: 16px;
  }
}

.user-details {
  display: flex;
  flex-direction: column;
  gap: 3px;
  overflow: hidden;
}

.user-name {
  font-size: 16px;
  font-weight: 600;
  color: #000;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;

  @media (max-width: 480px) {
    font-size: 15px;
  }
}

.user-email {
  font-size: 12px;
  color: #888;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.stats-container {
  display: flex;
  padding: 22px 20px;
  border-bottom: 1px solid #e8e8e8;

  @media (max-width: 480px) {
    padding: 18px 15px;
    gap: 15px;
  }
}

.stat-item {
  flex: 1;
  display: flex;
  gap: 10px;
  align-items: flex-start;

  @media (max-width: 480px) {
    gap: 8px;
  }
}

.stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  @media (max-width: 480px) {
    width: 40px;
    height: 40px;
  }

  svg {
    width: 24px;
    height: 24px;

    @media (max-width: 480px) {
      width: 20px;
      height: 20px;
    }
  }

  &.box-icon {
    background: #fef5eb;
  }

  &.sun-icon {
    background: #fffbf0;
  }
}

.stat-info {
  display: flex;
  flex-direction: column;
  gap: 3px;
  padding-top: 2px;
}

.stat-label {
  font-size: 11px;
  color: #888;
  line-height: 1.2;

  @media (max-width: 480px) {
    font-size: 10px;
  }
}

.stat-value {
  font-size: 18px;
  font-weight: 700;
  color: #000;
  line-height: 1.1;

  @media (max-width: 480px) {
    font-size: 16px;
  }
}

.membership-box {
  padding: 22px 20px;
  border-bottom: 1px solid #e8e8e8;

  @media (max-width: 480px) {
    padding: 18px 15px;
  }
}

.membership-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.membership-label {
  font-size: 11px;
  font-weight: 700;
  color: #000;
  letter-spacing: 0.5px;

  @media (max-width: 480px) {
    font-size: 10px;
  }
}

.info-svg {
  opacity: 0.5;
}

.progress-container {
  height: 6px;
  background: #e8e8e8;
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 10px;
}

.progress-bar {
  width: 3.8%;
  height: 100%;
  background: linear-gradient(to right, #8b0000, #a00000);
}

.points-info {
  font-size: 11px;
  color: #666;
  line-height: 1.3;

  @media (max-width: 480px) {
    font-size: 10px;
  }

  strong {
    font-weight: 700;
    color: #000;
  }
}

.earn-box {
  padding: 22px 20px;

  @media (max-width: 480px) {
    padding: 18px 15px;
  }
}

.earn-heading {
  font-size: 13px;
  font-weight: 700;
  color: #000;
  margin-bottom: 12px;

  @media (max-width: 480px) {
    font-size: 12px;
  }
}

.points-list {
  margin: 0;
  padding-left: 18px;
  font-size: 11px;
  color: #666;
  line-height: 1.5;

  @media (max-width: 480px) {
    font-size: 10px;
    padding-left: 16px;
  }

  li {
    margin-bottom: 8px;

    &:last-child {
      margin-bottom: 0;
    }
  }
}

.right-column {
  display: flex;
  background: #FFFBF3;
  flex-direction: column;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
  min-width: 0;

  @media (max-width: 768px) {
    order: 1;
  }
}

.nav-bar {
  border-bottom: 1px solid #ddd;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 30px;
  margin-bottom: 40px;
  width: 100%;
  box-sizing: border-box;

  @media (max-width: 768px) {
    padding: 0 15px;
    margin-bottom: 30px;
    overflow-x: hidden;
  }

  @media (max-width: 480px) {
    padding: 0;
    margin-bottom: 20px;
    flex-direction: column;
    align-items: stretch;
  }
}

.nav-links {
  display: flex;
  gap: 0;

  @media (max-width: 768px) {
    flex: 1;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;

    &::-webkit-scrollbar {
      display: none;
    }
  }
}

.nav-item {
  color: #666;
  text-decoration: none;
  font-size: 14px;
  padding: 26px 20px;
  position: relative;
  font-weight: 400;
  transition: color 0.2s;
  white-space: nowrap;

  @media (max-width: 768px) {
    padding: 20px 15px;
    font-size: 13px;
  }

  @media (max-width: 480px) {
    padding: 18px 12px;
    font-size: 12px;
  }

  &:hover {
    color: #8b0000;
  }

  &.active {
    color: #8b0000;
    font-weight: 500;

    &::after {
      content: "";
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: #8b0000;
    }
  }
}

.logout {
  color: #8b0000;
  font-size: 14px;
  cursor: pointer;
  text-decoration: none;
  font-weight: 400;

  @media (max-width: 768px) {
    font-size: 13px;
    padding: 20px 0;
  }

  @media (max-width: 480px) {
    font-size: 12px;
    padding: 18px 0;
  }

  &:hover {
    text-decoration: underline;
  }
}

.content-section {
  flex: 1;

  @media (max-width: 768px) {
    padding: 0 15px;
  }

  @media (max-width: 480px) {
    padding: 0 10px;
  }
}
</style>