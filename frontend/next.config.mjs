/** @type {import('next').NextConfig} */
const nextConfig = {
  reactStrictMode: true,
  images: {
    remotePatterns: [{ protocol: 'https', hostname: 'directorsoman.com', pathname: '/wp-content/uploads/2025/12/logo.svg' }],
  },
};

export default nextConfig;
