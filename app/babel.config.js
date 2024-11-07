module.exports = {
    presets: [
        ['@babel/preset-env', {
            targets: {
                esmodules: true,
            },
            useBuiltIns: 'usage',
            corejs: '3.23',
        }],
    ],
    plugins: [
        '@babel/plugin-proposal-optional-chaining',
    ],
};